<?php

namespace GlpiPlugin\G4freports\Export;

use GlpiPlugin\G4freports\Util\DateHelper;
use GlpiPlugin\G4freports\Util\TextNormalizer;

class DocxExporter
{
    private string $navy = '002B49';
    private string $blue = '2F6FED';
    private string $teal = '188DA1';
    private string $light = 'EAF1F8';
    private string $border = 'B8C7D9';
    private string $yellow = 'FFF4CC';
    private string $green = 'E9F7EF';
    private string $templatePath = '';
    private array $chartParts = [];
    private int $chartIndex = 0;

    public function __construct(string $templatePath = '')
    {
        // The exporter is dependency-free: no PHPWord and no ZipArchive.
        // When available, it uses the official G4F letterhead DOCX as the
        // package base, preserving the header/background/footer media exactly
        // as authored in Word. Only word/document.xml and document properties
        // are replaced with generated editable content.
        $defaultTemplate = dirname(__DIR__, 2) . '/resources/docx/papel_timbrado_G4F.docx';
        $this->templatePath = $templatePath !== '' ? $templatePath : $defaultTemplate;
    }

    public function toBinary(array $draft): string
    {
        if (is_file($this->templatePath) && is_readable($this->templatePath)) {
            $templateBinary = @file_get_contents($this->templatePath);
            if (is_string($templateBinary) && $templateBinary !== '') {
                $entries = $this->zipRead($templateBinary);
                if (isset($entries['word/document.xml'], $entries['word/_rels/document.xml.rels'])) {
                    $this->resetCharts();
                    $entries['word/document.xml'] = $this->documentXml($draft, true);
                    $this->attachChartParts($entries);
                    $entries['docProps/core.xml'] = $this->coreXml($draft);
                    $entries['docProps/app.xml'] = $this->appXml();
                    return $this->zipStore($entries);
                }
            }
        }

        // Fallback for emergency cases where the template file was removed.
        $this->resetCharts();
        $documentXml = $this->documentXml($draft, false);
        $files = [
            '[Content_Types].xml' => $this->contentTypesXml(),
            '_rels/.rels' => $this->rootRelsXml(),
            'docProps/core.xml' => $this->coreXml($draft),
            'docProps/app.xml' => $this->appXml(),
            'word/document.xml' => $documentXml,
            'word/_rels/document.xml.rels' => $this->documentRelsXml(),
            'word/styles.xml' => $this->stylesXml(),
            'word/settings.xml' => $this->settingsXml(),
            'word/header1.xml' => $this->headerXml(),
            'word/footer1.xml' => $this->footerXml((string)($draft['footer'] ?? '')),
        ];
        $this->attachChartParts($files);
        return $this->zipStore($files);
    }

    public function filename(array $draft): string
    {
        $title = (string)($draft['report_title'] ?? 'relatorio-g4f');
        $subject = $this->cleanPersonName((string)($draft['subject']['name'] ?? 'profissional'));
        $template = (string)($draft['template'] ?? 'operational');
        $period = (string)($draft['period_start'] ?? '') . '_' . (string)($draft['period_end'] ?? '');
        $base = $title . '-' . $template . '-' . $subject . '-' . $period;
        $base = iconv('UTF-8', 'ASCII//TRANSLIT//IGNORE', $base) ?: $base;
        $base = preg_replace('/[^A-Za-z0-9_\-]+/', '-', $base) ?? $base;
        $base = trim($base, '-_');
        if ($base === '') {
            $base = 'relatorio-g4f';
        }
        return substr($base, 0, 140) . '.docx';
    }

    private function documentXml(array $draft, bool $letterhead = false): string
    {
        $body = [];
        $body[] = $this->coverPage($draft, $letterhead);
        $body[] = $this->pageBreak();

        $template = (string)($draft['template'] ?? 'operational');
        switch ($template) {
            case 'formal_managerial':
                $body[] = $this->formalManagerialBody($draft);
                break;
            case 'project_technical':
                $body[] = $this->projectTechnicalBody($draft);
                break;
            case 'annex_full':
                $body[] = $this->annexFullBody($draft);
                break;
            case 'operational':
            default:
                $body[] = $this->operationalBody($draft);
                break;
        }

        $sectPr = $letterhead
            ? $this->letterheadSectPr()
            : '<w:sectPr><w:headerReference w:type="default" r:id="rIdHeader"/><w:footerReference w:type="default" r:id="rIdFooter"/><w:pgSz w:w="11906" w:h="16838"/><w:pgMar w:top="1134" w:right="1134" w:bottom="1134" w:left="1134" w:header="708" w:footer="708" w:gutter="0"/></w:sectPr>';

        return '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>'
            . '<w:document xmlns:w="http://schemas.openxmlformats.org/wordprocessingml/2006/main" xmlns:r="http://schemas.openxmlformats.org/officeDocument/2006/relationships" xmlns:wp="http://schemas.openxmlformats.org/drawingml/2006/wordprocessingDrawing" xmlns:a="http://schemas.openxmlformats.org/drawingml/2006/main" xmlns:c="http://schemas.openxmlformats.org/drawingml/2006/chart">'
            . '<w:background w:color="FFFFFF"/><w:body>' . implode('', $body) . $sectPr . '</w:body></w:document>';
    }

    private function coverPage(array $draft, bool $letterhead = false): string
    {
        $title = (string)($draft['report_title'] ?? 'Relatório Gerencial de Atividades');
        $subject = $this->cleanPersonName((string)($draft['subject']['name'] ?? 'Profissional não informado'));
        $period = DateHelper::displayDate((string)($draft['period_start'] ?? '')) . ' a ' . DateHelper::displayDate((string)($draft['period_end'] ?? ''));
        $subtitleParts = array_filter([
            (string)($draft['contract_label'] ?? ''),
            (string)($draft['connection']['name'] ?? 'GLPI'),
            $this->templateLabel((string)($draft['template'] ?? 'operational'))
        ], static fn($v) => trim((string)$v) !== '');

        $xml = '';
        $xml .= $letterhead ? $this->spacer(42) : ($this->brandMark(28, 'left') . $this->spacer(22));
        $xml .= $this->paragraph($title, 'Title', 'center', true, 48, $this->navy);
        $xml .= $this->paragraph(implode(' • ', $subtitleParts), '', 'center', false, 22, '666666');
        $xml .= $this->spacer(10);
        $xml .= $this->paragraph('Profissional responsável: ' . $subject, '', 'center', false, 22, '666666');
        $xml .= $this->paragraph('Período: ' . $period, '', 'center', false, 22, '666666');
        $xml .= $this->spacer(12);
        $xml .= $this->metricCards($draft['metrics'] ?? []);
        $xml .= $this->callout('Leitura recomendada', 'Este documento é um rascunho normalizado gerado a partir da API do GLPI e de complementações narrativas. Revise as atividades, status, comentários extraídos e pendências antes da versão final.', 'DCEBFF', $this->blue);
        $xml .= $this->spacer(18);
        $xml .= $this->paragraph('Data de emissão: ' . date('d/m/Y'), '', 'center', false, 20, '666666');
        if (!$letterhead) {
            $xml .= $this->spacer(26);
            $xml .= $this->decorativeStripes();
        }
        return $xml;
    }

    private function operationalBody(array $draft): string
    {
        $subject = $this->cleanPersonName((string)($draft['subject']['name'] ?? 'Profissional responsável'));
        $xml = '';
        $xml .= $this->heading('Sumário executivo', 1);
        $xml .= $this->blockParagraphs((string)($draft['executive_summary'] ?? ''), 22);
        $xml .= $this->heading('1. Abrangência', 1);
        $xml .= $this->blockParagraphs((string)($draft['scope_text'] ?? ''), 22);
        $xml .= $this->heading('2. Objetivo', 1);
        $xml .= $this->blockParagraphs((string)($draft['objective_text'] ?? ''), 22);
        $xml .= $this->heading('3. Indicadores consolidados', 1);
        $xml .= $this->indicatorTable($draft['metrics'] ?? []);
        $xml .= $this->analyticsBlock($draft, 'operational', ['consolidated_table']);
        $xml .= $this->heading('4. Evolução diária das atividades', 1);
        $xml .= $this->analyticsBlock($draft, 'operational', ['daily_volume','daily_heatmap']);
        $xml .= $this->validationsBlock($draft);
        if ($this->isManagerMode($draft)) {
            $xml .= $this->heading('5. Resumo gerencial por agente', 1);
            $xml .= $this->managerSummaryBlock($draft);
        } else {
            $xml .= $this->heading('5. Detalhamento diário', 1);
            $xml .= $this->dailyDetail($draft, false);
        }
        $xml .= $this->heading('6. Distribuições e desempenho', 1);
        $xml .= $this->analyticsBlock($draft, 'operational', ['status_distribution','source_distribution','top_categories','location_distribution','agent_vs_group','response_time']);
        $xml .= $this->heading('7. Pendências e próximos passos', 1);
        $xml .= $this->blockParagraphs($this->collectNextSteps($draft) ?: 'Não foram registradas pendências específicas no rascunho. Complementar manualmente, caso aplicável.', 22);
        $xml .= $this->heading('8. Conclusão', 1);
        $xml .= $this->blockParagraphs((string)($draft['conclusion_text'] ?? 'As atividades foram consolidadas em formato padronizado para facilitar conferência, ajuste narrativo e envio gerencial. O documento deve ser revisado pelo responsável antes da formalização.'), 22);
        $xml .= $this->signatureBlock($subject);
        return $xml;
    }

    private function formalManagerialBody(array $draft): string
    {
        $subject = $this->cleanPersonName((string)($draft['subject']['name'] ?? 'Profissional responsável'));
        $xml = '';
        $xml .= $this->heading('Sumário executivo', 1);
        $xml .= $this->blockParagraphs((string)($draft['executive_summary'] ?? ''), 22);
        $xml .= $this->callout('Regra de leitura gerencial', 'O relatório formal prioriza resultado, rastreabilidade e pendências. Detalhes integrais de chamados, tarefas e acompanhamentos devem permanecer no rascunho ou no anexo evidencial quando necessário.', $this->yellow, 'D39E00');
        $xml .= $this->heading('1. Principais entregas do período', 1);
        $xml .= $this->deliveriesTable($draft, 12);
        $xml .= $this->heading('2. Indicadores consolidados', 1);
        $xml .= $this->indicatorTable($draft['metrics'] ?? []);
        $xml .= $this->heading('3. Indicadores visuais e tempo de resposta', 1);
        $xml .= $this->analyticsBlock($draft, 'formal_managerial', ['consolidated_table','status_distribution','response_time']);
        $xml .= $this->heading('4. Evolução e volumetria diária', 1);
        $xml .= $this->analyticsBlock($draft, 'formal_managerial', ['daily_volume','daily_heatmap']);
        $xml .= $this->heading('5. Atividades por origem', 1);
        $xml .= $this->sourceSummaryTable($draft);
        $xml .= $this->analyticsBlock($draft, 'formal_managerial', ['source_distribution','agent_vs_group']);
        $xml .= $this->heading('6. Categorias, localização e concentração', 1);
        $xml .= $this->analyticsBlock($draft, 'formal_managerial', ['top_categories','location_distribution']);
        $xml .= $this->heading('7. Riscos, pendências e encaminhamentos', 1);
        $xml .= $this->blockParagraphs($this->riskAndPendingText($draft), 22);
        if ($this->isManagerMode($draft)) {
            $xml .= $this->heading('8. Resumo gerencial por agente', 1);
            $xml .= $this->managerSummaryBlock($draft);
        } else {
            $xml .= $this->heading('8. Detalhamento sintético por dia', 1);
            $xml .= $this->dailyDetail($draft, true);
        }
        $xml .= $this->heading('9. Conclusão executiva', 1);
        $xml .= $this->blockParagraphs((string)($draft['conclusion_text'] ?? 'O período foi consolidado com foco em padronização das evidências, rastreabilidade das ações executadas e identificação de pendências para continuidade operacional.'), 22);
        $xml .= $this->signatureBlock($subject);
        return $xml;
    }

    private function projectTechnicalBody(array $draft): string
    {
        $subject = $this->cleanPersonName((string)($draft['subject']['name'] ?? 'Profissional responsável'));
        $xml = '';
        $xml .= $this->heading('Sumário executivo técnico', 1);
        $xml .= $this->blockParagraphs((string)($draft['executive_summary'] ?? ''), 22);
        $xml .= $this->heading('1. Contexto e escopo técnico', 1);
        $xml .= $this->blockParagraphs((string)($draft['scope_text'] ?? ''), 22);
        $xml .= $this->heading('2. Objetivo', 1);
        $xml .= $this->blockParagraphs((string)($draft['objective_text'] ?? ''), 22);
        $xml .= $this->heading('3. Projetos, chamados e tarefas relacionadas', 1);
        $xml .= $this->sourceSummaryTable($draft);
        $xml .= $this->heading('4. Indicadores e distribuição técnica', 1);
        $xml .= $this->analyticsBlock($draft, 'project_technical', ['consolidated_table','status_distribution','source_distribution']);
        $xml .= $this->heading('5. Evolução das atividades', 1);
        $xml .= $this->analyticsBlock($draft, 'project_technical', ['daily_volume','daily_heatmap']);
        if ($this->isManagerMode($draft)) {
            $xml .= $this->heading('6. Resumo gerencial por agente', 1);
            $xml .= $this->managerSummaryBlock($draft);
        } else {
            $xml .= $this->heading('6. Atividades técnicas executadas', 1);
            $xml .= $this->dailyDetail($draft, false);
        }
        $xml .= $this->analyticsBlock($draft, 'project_technical', ['top_categories','response_time','agent_vs_group','location_distribution']);
        $xml .= $this->heading('7. Decisões, pendências e próximos passos', 1);
        $xml .= $this->blockParagraphs($this->collectNextSteps($draft) ?: 'Não foram registradas pendências específicas. Complementar decisões técnicas e próximos passos, caso aplicável.', 22);
        $xml .= $this->heading('8. Validações do rascunho', 1);
        $xml .= $this->validationsBlock($draft) ?: $this->paragraph('Nenhuma validação relevante registrada.', '', 'left', false, 22, '333333');
        $xml .= $this->heading('9. Conclusão técnica', 1);
        $xml .= $this->blockParagraphs((string)($draft['conclusion_text'] ?? 'As ações técnicas foram consolidadas a partir dos registros relacionados ao profissional, aos grupos e aos projetos selecionados. Recomenda-se revisão final para incluir decisões técnicas que não estejam refletidas no GLPI.'), 22);
        $xml .= $this->signatureBlock($subject);
        return $xml;
    }

    private function annexFullBody(array $draft): string
    {
        $xml = '';
        $xml .= $this->heading('Anexo evidencial completo', 1);
        $xml .= $this->blockParagraphs('Este anexo mantém o detalhamento integral das entradas incluídas no rascunho. Use este modelo quando a prioridade for evidência e rastreabilidade, não síntese gerencial.', 22);
        $xml .= $this->heading('1. Metadados do rascunho', 1);
        $xml .= $this->simpleTable(['Campo', 'Valor'], [
            ['Modelo', $this->templateLabel((string)($draft['template'] ?? 'annex_full'))],
            ['Profissional', $this->cleanPersonName((string)($draft['subject']['name'] ?? ''))],
            ['Período', DateHelper::displayDate((string)($draft['period_start'] ?? '')) . ' a ' . DateHelper::displayDate((string)($draft['period_end'] ?? ''))],
            ['Conexão', (string)($draft['connection']['name'] ?? '')],
            ['Contextos', implode(', ', is_array($draft['contexts'] ?? null) ? $draft['contexts'] : [])],
            ['Contexto de dados', $this->dataContextSummary($draft)],
        ]);
        $xml .= $this->heading('2. Indicadores', 1);
        $xml .= $this->indicatorTable($draft['metrics'] ?? []);
        $xml .= $this->heading('3. Análises e tabelas consolidadas', 1);
        $xml .= $this->analyticsBlock($draft, 'annex_full');
        if ($this->isManagerMode($draft)) {
            $xml .= $this->heading('4. Resumo gerencial por agente', 1);
            $xml .= $this->managerSummaryBlock($draft);
            $xml .= $this->callout('Detalhamento individual omitido', 'O modo gerencial estava ativo no momento da exportação. Para gerar evidência por chamado, desative o modo gerencial ou aplique filtros por título/período mais restritos.', $this->light, $this->blue);
        } else {
            $xml .= $this->heading('4. Evidências por data', 1);
            $xml .= $this->dailyDetail($draft, false, true);
        }
        return $xml;
    }

    private function dailyDetail(array $draft, bool $compact = false, bool $annex = false): string
    {
        $daily = $this->dailyGroups($draft);
        if (empty($daily)) {
            return $this->paragraph('Nenhuma entrada foi incluída no relatório para o período selecionado.', '', 'left', false, 22, '333333');
        }
        $xml = '';
        foreach ($daily as $group) {
            $xml .= $this->heading(DateHelper::displayDate((string)$group['date']), 2);
            if ($compact) {
                $rows = [];
                foreach ($group['entries'] as $entry) {
                    $rows[] = [
                        (string)($entry['source_reference'] ?? ''),
                        (string)($entry['title'] ?? ''),
                        (string)($entry['status'] ?? ''),
                        TextNormalizer::summarize((string)($entry['activity_performed'] ?? ''), 260),
                    ];
                }
                $xml .= $this->simpleTable(['Referência', 'Atividade', 'Status', 'Síntese'], $rows);
                continue;
            }
            foreach ($group['entries'] as $entry) {
                $xml .= $this->entryBlock($entry, $annex);
            }
        }
        return $xml;
    }

    private function dailyGroups(array $draft): array
    {
        $groups = [];
        foreach ($this->entries($draft) as $entry) {
            $date = DateHelper::normalizeDate((string)($entry['date'] ?? ''), date('Y-m-d'));
            if (!isset($groups[$date])) {
                $groups[$date] = ['date' => $date, 'entries' => []];
            }
            $groups[$date]['entries'][] = $entry;
        }
        ksort($groups);
        return array_values($groups);
    }

    private function entries(array $draft): array
    {
        if ($this->isManagerMode($draft)) {
            return [];
        }
        $entries = is_array($draft['detail_entries'] ?? null) ? $draft['detail_entries'] : (is_array($draft['entries'] ?? null) ? $draft['entries'] : []);
        return array_values(array_filter($entries, static fn($entry) => is_array($entry) && !empty($entry['include_in_report'])));
    }


    private function isManagerMode(array $draft): bool
    {
        return !empty($draft['data_context']['manager_mode_enabled']) || !empty($draft['manager_context']['enabled']);
    }

    private function dataContextSummary(array $draft): string
    {
        $dc = is_array($draft['data_context'] ?? null) ? $draft['data_context'] : [];
        $parts = [];
        if (!empty($dc['manager_mode_enabled'])) {
            $parts[] = 'Modo gerencial ativo';
        }
        if (!empty($dc['ticket_title_filter_enabled']) && !empty($dc['ticket_titles']) && is_array($dc['ticket_titles'])) {
            $titles = [];
            foreach ($dc['ticket_titles'] as $row) {
                if (is_array($row) && trim((string)($row['title'] ?? '')) !== '') {
                    $titles[] = (string)$row['title'];
                }
            }
            $scope = (string)($dc['effective_ticket_title_filter_scope'] ?? $dc['ticket_title_filter_scope'] ?? 'detail_only');
            $parts[] = 'Filtro por título (' . ($scope === 'global' ? 'global' : 'somente detalhamento') . '): ' . implode('; ', array_slice($titles, 0, 12));
        }
        return !empty($parts) ? implode(' | ', $parts) : 'Sem contexto de dados adicional.';
    }

    private function managerSummaryBlock(array $draft): string
    {
        $analytics = is_array($draft['analytics'] ?? null) ? $draft['analytics'] : [];
        $rows = $analytics['manager_summary']['by_agent'] ?? [];
        if (!is_array($rows) || empty($rows)) {
            return $this->paragraph('Nenhum resumo por agente foi calculado. Verifique se os contextos por grupo e as análises de tempo de resposta/atividade técnica foram selecionados.', '', 'left', false, 22, '333333');
        }
        $tableRows = [];
        foreach (array_slice($rows, 0, 80) as $row) {
            if (!is_array($row)) continue;
            $agentId = (int)($row['agent_id'] ?? 0);
            $reference = trim((string)($row['agent_login'] ?? ''));
            if ($reference === '' && isset($row['reference'])) {
                $reference = trim((string)$row['reference']);
            }
            if ($reference === '' && $agentId > 0) {
                $reference = '#' . $agentId;
            } elseif ($reference !== '' && $agentId > 0 && strpos($reference, '#') !== 0) {
                $reference = '#' . $agentId . ' · ' . $reference;
            }
            $tableRows[] = [
                (string)($row['agent_name'] ?? 'Técnico não identificado'),
                $reference !== '' ? $reference : 'N/D',
                (string)($row['tickets_touched'] ?? 0),
                (string)($row['tickets_closed'] ?? 0),
                (string)($row['technical_events'] ?? 0),
                $this->durationLabel($row['avg_response_minutes'] ?? null),
                $this->durationLabel($row['p90_response_minutes'] ?? null),
            ];
        }
        $xml = $this->callout('Leitura gerencial', 'O modo gerencial omite o detalhamento individual de chamados e consolida volume, conclusão e tempo de resposta por agente detectado nos eventos técnicos dos chamados dos grupos selecionados.', $this->light, $this->blue);
        $xml .= $this->simpleTable(['Agente','Login/ID','Chamados tocados','Concluídos/solucionados','Eventos técnicos','Resposta média','P90 resposta'], $tableRows);
        return $xml;
    }

    private function entryBlock(array $entry, bool $annex = false): string
    {
        $title = (string)($entry['title'] ?? 'Atividade');
        $ref = trim((string)($entry['source_reference'] ?? ''));
        $status = trim((string)($entry['status'] ?? ''));
        $category = trim((string)($entry['category'] ?? ''));
        $meta = [];
        if ($ref !== '') $meta[] = $ref;
        if ($status !== '') $meta[] = 'Status: ' . $status;
        if ($category !== '') $meta[] = 'Categoria: ' . $category;
        if ($annex) {
            $meta[] = 'Origem: ' . (string)($entry['source_type'] ?? '');
            if (!empty($entry['source_itemtype'])) $meta[] = 'Itemtype: ' . (string)$entry['source_itemtype'];
            if (!empty($entry['source_id'])) $meta[] = 'ID origem: ' . (int)$entry['source_id'];
        }

        $xml = '<w:tbl><w:tblPr><w:tblStyle w:val="TableGrid"/><w:tblW w:w="9500" w:type="dxa"/><w:tblBorders>' . $this->borders($this->border) . '</w:tblBorders></w:tblPr><w:tblGrid><w:gridCol w:w="9500"/></w:tblGrid>';
        $xml .= '<w:tr><w:tc><w:tcPr><w:shd w:fill="' . $this->light . '"/><w:tcW w:w="9500" w:type="dxa"/></w:tcPr>';
        $xml .= $this->paragraph($title, '', 'left', true, 22, $this->navy);
        if (!empty($meta)) {
            $xml .= $this->paragraph(implode(' | ', $meta), '', 'left', false, 18, '666666');
        }
        $xml .= '</w:tc></w:tr>';
        $xml .= '<w:tr><w:tc><w:tcPr><w:tcW w:w="9500" w:type="dxa"/></w:tcPr>';
        $xml .= $this->labelAndBlock('Atividades realizadas', (string)($entry['activity_performed'] ?? ''));
        $xml .= $this->labelAndBlock('Resultado', (string)($entry['result'] ?? ''));
        if (trim((string)($entry['next_steps'] ?? '')) !== '') {
            $xml .= $this->labelAndBlock('Plano de tratamento / próximos passos', (string)$entry['next_steps']);
        }
        if (trim((string)($entry['observations'] ?? '')) !== '') {
            $xml .= $this->labelAndBlock('Observações', (string)$entry['observations']);
        }
        $xml .= '</w:tc></w:tr></w:tbl>';
        return $xml . $this->spacer(4);
    }

    private function labelAndBlock(string $label, string $text): string
    {
        $text = trim($text);
        if ($text === '') return '';
        return $this->paragraph($label, '', 'left', true, 20, $this->navy) . $this->blockParagraphs($text, 20);
    }

    private function deliveriesTable(array $draft, int $limit): string
    {
        $rows = [];
        foreach (array_slice($this->entries($draft), 0, $limit) as $entry) {
            $rows[] = [
                DateHelper::displayDate((string)($entry['date'] ?? '')),
                (string)($entry['source_reference'] ?? ''),
                (string)($entry['title'] ?? ''),
                TextNormalizer::summarize(TextNormalizer::firstNonEmpty($entry['result'] ?? '', $entry['activity_performed'] ?? ''), 240),
            ];
        }
        if (empty($rows)) {
            $rows[] = ['-', '-', 'Sem entregas extraídas', 'Complementar manualmente se necessário.'];
        }
        return $this->simpleTable(['Data', 'Referência', 'Entrega/atividade', 'Resultado'], $rows);
    }

    private function sourceSummaryTable(array $draft): string
    {
        $counts = [];
        foreach ($this->entries($draft) as $entry) {
            $src = (string)($entry['source_type'] ?? 'manual');
            if ($src === '') $src = 'manual';
            $counts[$src] = ($counts[$src] ?? 0) + 1;
        }
        $rows = [];
        foreach ($counts as $src => $count) {
            $rows[] = [$this->sourceLabel($src), (string)$count];
        }
        if (empty($rows)) $rows[] = ['Sem entradas', '0'];
        return $this->simpleTable(['Origem', 'Quantidade'], $rows);
    }

    private function riskAndPendingText(array $draft): string
    {
        $lines = [];
        $next = $this->collectNextSteps($draft);
        if ($next !== '') {
            $lines[] = $next;
        }
        foreach (($draft['validations'] ?? []) as $validation) {
            if (!is_array($validation)) continue;
            $message = (string)($validation['message'] ?? '');
            if (!$this->shouldExportValidation($message, $draft)) continue;
            $lines[] = '• ' . strtoupper((string)($validation['level'] ?? 'info')) . ': ' . $message;
        }
        foreach (($draft['warnings'] ?? []) as $warning) {
            $message = (string)$warning;
            if (!$this->shouldExportValidation($message, $draft)) continue;
            $lines[] = '• ALERTA: ' . $message;
        }
        return implode("\n", $lines) ?: 'Não foram identificadas pendências ou riscos automáticos no rascunho. Complementar manualmente, caso existam riscos operacionais fora do GLPI.';
    }

    private function collectNextSteps(array $draft): string
    {
        $lines = [];
        foreach ($this->entries($draft) as $entry) {
            $next = trim((string)($entry['next_steps'] ?? ''));
            if ($next !== '') {
                $lines[] = '• ' . ((string)($entry['title'] ?? 'Atividade')) . ': ' . $next;
            }
        }
        return implode("\n", array_slice($lines, 0, 50));
    }

    private function validationsBlock(array $draft): string
    {
        $txt = [];
        foreach (($draft['validations'] ?? []) as $validation) {
            if (!is_array($validation)) continue;
            $message = (string)($validation['message'] ?? '');
            if (!$this->shouldExportValidation($message, $draft)) continue;
            $txt[] = strtoupper((string)($validation['level'] ?? 'info')) . ': ' . $message;
        }
        foreach (($draft['warnings'] ?? []) as $warning) {
            $message = (string)$warning;
            if (!$this->shouldExportValidation($message, $draft)) continue;
            $txt[] = 'ALERTA: ' . $message;
        }
        if (empty($txt)) return '';
        return $this->callout('Validações do rascunho', implode("\n", $txt), $this->yellow, 'D39E00');
    }


    private function analyticsBlock(array $draft, string $mode = 'default', array $onlyKeys = []): string
    {
        $analytics = is_array($draft['analytics'] ?? null) ? $draft['analytics'] : [];
        if (empty($analytics)) {
            return $this->paragraph('Nenhuma análise quantitativa foi selecionada ou calculada para este rascunho.', '', 'left', false, 22, '333333');
        }
        $keys = !empty($onlyKeys) ? $onlyKeys : ['consolidated_table','manager_summary','daily_volume','daily_heatmap','status_distribution','source_distribution','agent_vs_group','top_categories','location_distribution','response_time'];
        $xml = '';
        foreach ($keys as $key) {
            $xml .= $this->analyticsKeyXml($analytics, $key);
        }
        return $xml !== '' ? $xml : $this->paragraph('As análises selecionadas não retornaram dados suficientes para composição de gráficos/tabelas.', '', 'left', false, 22, '333333');
    }

    private function analyticsKeyXml(array $analytics, string $key): string
    {
        if ($key === 'manager_summary') {
            if (empty($analytics['manager_summary']) || !is_array($analytics['manager_summary'])) return '';
            return $this->heading('Resumo gerencial por agente', 2) . $this->managerSummaryBlock(['analytics' => $analytics, 'data_context' => ['manager_mode_enabled' => true]]);
        }
        if ($key === 'response_time' || $key === 'avg_response_time' || $key === 'first_response_time' || $key === 'unanswered_events') {
            if (empty($analytics['response_time']) || !is_array($analytics['response_time'])) return '';
            $rt = $analytics['response_time'];
            $rows = [];
            foreach (($rt['by_day'] ?? []) as $day => $minutes) {
                if (is_numeric($minutes)) $rows[] = ['label' => DateHelper::displayDate((string)$day), 'value' => (float)$minutes];
            }
            $xml = $this->heading('Tempo de resposta técnico', 2);
            if (!empty($rows)) $xml .= $this->nativeChart('line', 'Evolução do tempo médio de resposta', $rows, 8800, 3100);
            $xml .= $this->responseTimeTable($rt);
            $xml .= $this->callout('Critério de cálculo', 'O tempo de resposta considera ciclos entre evento do solicitante e a primeira ação técnica posterior do agente ou dos grupos selecionados. Períodos aguardando retorno do solicitante não são atribuídos ao profissional ou à equipe.', $this->light, $this->blue);
            return $xml;
        }
        if ($key === 'consolidated_table') {
            $rows = [];
            foreach (($analytics['consolidated_table'] ?? []) as $row) if (is_array($row)) $rows[] = [(string)($row['indicator'] ?? ''), (string)($row['value'] ?? '')];
            return !empty($rows) ? $this->heading('Tabela consolidada de indicadores', 2) . $this->simpleTable(['Indicador','Valor'], $rows) : '';
        }
        if ($key === 'daily_heatmap') {
            $source = $analytics['daily_heatmap'] ?? ($analytics['daily_volume'] ?? []);
            $rows = $this->analyticsRows($source, 'daily_volume');
            return !empty($rows) ? $this->heatmapTable($rows) : '';
        }
        if (empty($analytics[$key])) return '';
        $rows = $this->analyticsRows($analytics[$key], $key);
        if (empty($rows)) return '';
        $titles = [
            'daily_volume'=>'Volume de atividades por dia',
            'status_distribution'=>'Distribuição por status',
            'source_distribution'=>'Origem das atividades',
            'top_categories'=>'Principais categorias',
            'location_distribution'=>'Localização dos chamados',
            'agent_vs_group'=>'Agente x grupos selecionados'
        ];
        $title = $titles[$key] ?? $key;
        if ($key === 'daily_volume') {
            return $this->heading($title, 2)
                . $this->nativeChart('line', 'Evolução diária de atividades', $rows, 8800, 3100)
                . $this->sparklineSummary($rows);
        }
        if ($key === 'status_distribution') {
            return $this->heading($title, 2) . $this->nativeChart('doughnut', $title, $rows, 6600, 3100) . $this->legendTable($rows, 'Leitura por status');
        }
        if ($key === 'source_distribution') {
            return $this->heading($title, 2) . $this->nativeChart('pie', $title, $rows, 6600, 3100) . $this->legendTable($rows, 'Leitura por origem');
        }
        if ($key === 'agent_vs_group') {
            return $this->heading($title, 2) . $this->nativeChart('column', $title, $rows, 7600, 3000) . $this->compactDistributionTable($rows);
        }
        if ($key === 'location_distribution') {
            return $this->heading($title, 2) . $this->nativeChart('doughnut', $title, $rows, 7600, 3100) . $this->legendTable($rows, 'Leitura por localização');
        }
        if ($key === 'top_categories') {
            return $this->heading($title, 2) . $this->nativeChart('bar', $title, $rows, 8800, 3500) . $this->barTable($title, $rows, 12);
        }
        return $this->heading($title, 2) . $this->barTable($title, $rows, 12);
    }

    private function analyticsRows($data, string $key = ''): array
    {
        $rows = [];
        if (!is_array($data)) return [];
        $labels = ['agent'=>'Agente','selected_groups'=>'Grupos selecionados','mixed'=>'Misto','other'=>'Outros'];
        $isList = array_keys($data) === range(0, count($data) - 1);
        if ($isList) {
            foreach ($data as $row) if (is_array($row)) $rows[] = ['label'=>(string)($row['label'] ?? $row[0] ?? 'N/I'), 'value'=>(float)($row['value'] ?? $row[1] ?? 0)];
        } else {
            foreach ($data as $label => $value) $rows[] = ['label'=>$labels[(string)$label] ?? (string)$label, 'value'=>(float)$value];
        }
        if ($key === 'daily_volume') usort($rows, static fn($a,$b) => strcmp((string)$a['label'], (string)$b['label']));
        else usort($rows, static fn($a,$b) => ($b['value'] <=> $a['value']) ?: strcmp((string)$a['label'], (string)$b['label']));
        return $rows;
    }

    private function barTable(string $title, array $rows, int $limit = 12): string
    {
        $rows = array_slice($rows, 0, $limit);
        if (empty($rows)) return $this->paragraph('Sem dados para exibição.', '', 'left', false, 20, '333333');
        $max = 1.0;
        foreach ($rows as $row) $max = max($max, (float)($row['value'] ?? 0));
        $tableRows = [];
        foreach ($rows as $row) {
            $value = (float)($row['value'] ?? 0);
            $bar = str_repeat('█', max(1, (int)round(($value / max(1.0, $max)) * 20)));
            $tableRows[] = [(string)($row['label'] ?? 'N/I'), $this->formatNumber($value), $bar];
        }
        return $this->simpleTable(['Item','Qtde.','Distribuição visual'], $tableRows);
    }

    private function legendTable(array $rows, string $caption): string
    {
        $total = 0.0;
        foreach ($rows as $row) $total += (float)($row['value'] ?? 0);
        if ($total <= 0) return '';
        $tableRows = [];
        foreach (array_slice($rows, 0, 10) as $row) {
            $value = (float)($row['value'] ?? 0);
            $tableRows[] = [(string)($row['label'] ?? 'N/I'), $this->formatNumber($value), number_format(($value / $total) * 100, 1, ',', '.') . '%'];
        }
        return $this->simpleTable(['Segmento','Quantidade','%'], $tableRows)
            . $this->paragraph($caption . ': o gráfico de pizza/rosca destaca participação relativa dos segmentos e a tabela mantém a informação editável no Word.', '', 'left', false, 18, '666666');
    }

    private function compactDistributionTable(array $rows): string
    {
        $tableRows = [];
        foreach (array_slice($rows, 0, 8) as $row) $tableRows[] = [(string)($row['label'] ?? 'N/I'), $this->formatNumber((float)($row['value'] ?? 0))];
        return !empty($tableRows) ? $this->simpleTable(['Grupo','Quantidade'], $tableRows) : '';
    }

    private function sparklineSummary(array $rows): string
    {
        if (empty($rows)) return '';
        $values = array_map(static fn($r) => (float)($r['value'] ?? 0), $rows);
        $chars = ['▁','▂','▃','▄','▅','▆','▇','█'];
        $min = min($values); $max = max($values); $range = max(1.0, $max - $min); $spark = '';
        foreach ($values as $v) $spark .= $chars[max(0, min(7, (int)round((($v - $min) / $range) * 7)))];
        $first = (string)($rows[0]['label'] ?? ''); $last = (string)($rows[count($rows)-1]['label'] ?? '');
        return $this->simpleTable(['Série','Período','Total'], [[$spark, DateHelper::displayDate($first) . ' a ' . DateHelper::displayDate($last), $this->formatNumber(array_sum($values))]]);
    }

    private function heatmapTable(array $rows): string
    {
        $byDate = []; $max = 1.0;
        foreach ($rows as $row) {
            $date = DateHelper::normalizeDate((string)($row['label'] ?? ''), '');
            if ($date === '') continue;
            $value = (float)($row['value'] ?? 0);
            $byDate[$date] = $value; $max = max($max, $value);
        }
        if (empty($byDate)) return '';
        ksort($byDate);
        $start = new \DateTimeImmutable(array_key_first($byDate));
        $end = new \DateTimeImmutable(array_key_last($byDate));
        $cursor = $start->modify('monday this week');
        $last = $end->modify('sunday this week');
        $headers = ['Semana','Seg','Ter','Qua','Qui','Sex','Sáb','Dom'];
        $width = (int)floor(9500 / count($headers));
        $xml = $this->heading('Mapa de calor do volume por dia', 2);
        $xml .= '<w:tbl><w:tblPr><w:tblW w:w="9500" w:type="dxa"/><w:tblBorders>' . $this->borders($this->border) . '</w:tblBorders></w:tblPr><w:tblGrid>' . str_repeat('<w:gridCol w:w="' . $width . '"/>', count($headers)) . '</w:tblGrid><w:tr>';
        foreach ($headers as $h) $xml .= $this->cell($h, $this->navy, 'FFFFFF', true, $width);
        $xml .= '</w:tr>';
        $week = 1;
        while ($cursor <= $last && $week <= 8) {
            $xml .= '<w:tr>' . $this->cell('S' . $week, 'F6F9FC', $this->navy, true, $width);
            for ($i = 0; $i < 7; $i++) {
                $d = $cursor->modify('+' . $i . ' day')->format('Y-m-d');
                $v = (float)($byDate[$d] ?? 0);
                $fill = $v <= 0 ? 'F8FAFC' : ($v / $max >= .75 ? '2F6FED' : ($v / $max >= .5 ? '8CC2FF' : ($v / $max >= .25 ? 'DCEBFF' : 'EAF1F8')));
                $xml .= $this->cell($v > 0 ? DateHelper::displayDate($d) . "\n" . $this->formatNumber($v) : '-', $fill, '222222', $v > 0, $width);
            }
            $xml .= '</w:tr>'; $cursor = $cursor->modify('+7 day'); $week++;
        }
        return $xml . '</w:tbl>' . $this->spacer(6) . $this->paragraph('Leitura da figura: células mais intensas indicam dias com maior volume de registros no período.', '', 'left', false, 18, '666666');
    }

    private function responseTimeTable(array $rt): string
    {
        $rows = [
            ['Intervalos respondidos', (string)($rt['intervals_count'] ?? 0)],
            ['Chamados com linha do tempo', (string)($rt['tickets_with_timeline'] ?? 0)],
            ['Tempo médio de resposta', $this->durationLabel($rt['avg_minutes'] ?? null)],
            ['Mediana de resposta', $this->durationLabel($rt['median_minutes'] ?? null)],
            ['P90 de resposta', $this->durationLabel($rt['p90_minutes'] ?? null)],
            ['Maior tempo de resposta', $this->durationLabel($rt['max_minutes'] ?? null)],
            ['Tempo médio de primeira resposta', $this->durationLabel($rt['first_avg_minutes'] ?? null)],
            ['Primeiras respostas consideradas', (string)($rt['first_count'] ?? 0)],
            ['Eventos do solicitante sem resposta técnica posterior', (string)($rt['requester_events_unanswered'] ?? 0)],
        ];
        return $this->simpleTable(['Indicador', 'Valor'], $rows);
    }

    private function durationLabel($minutes): string
    {
        if ($minutes === null || $minutes === '' || !is_numeric($minutes)) {
            return 'N/D';
        }
        $minutes = (int)round((float)$minutes);
        if ($minutes < 60) {
            return $minutes . ' min';
        }
        $hours = intdiv($minutes, 60);
        $min = $minutes % 60;
        if ($hours < 24) {
            return $hours . 'h' . ($min > 0 ? str_pad((string)$min, 2, '0', STR_PAD_LEFT) : '');
        }
        $days = intdiv($hours, 24);
        $remHours = $hours % 24;
        return $days . 'd' . ($remHours > 0 ? ' ' . $remHours . 'h' : '');
    }

    private function formatNumber($value): string
    {
        if (is_numeric($value) && abs((float)$value - round((float)$value)) < 0.0001) {
            return (string)(int)round((float)$value);
        }
        if (is_numeric($value)) {
            return number_format((float)$value, 1, ',', '.');
        }
        return (string)$value;
    }

    private function shouldExportValidation(string $message, array $draft): bool
    {
        $message = trim($message);
        if ($message === '') {
            return false;
        }
        if (!empty($this->entries($draft)) && stripos($message, 'Nenhuma atividade foi retornada pela API') !== false) {
            return false;
        }
        return !preg_match('/GLPI API returned|ID de campo inválido|Falha ao pesquisar atividades em|ERROR_RANGE_EXCEED_TOTAL|Limite interno de tempo atingido|api\.php\/v[0-9]|\["ERROR"|corpo json inválido/i', $message);
    }

    private function cleanPersonName(string $name): string
    {
        $name = TextNormalizer::clean($name, false);
        $name = preg_replace('/\s+/', ' ', $name) ?? $name;
        $name = preg_replace('/^[\s\.]+|[\s\.]+$/u', '', trim($name)) ?? trim($name);
        return $name !== '' ? $name : 'Profissional não informado';
    }

    private function metricCards(array $metrics): string
    {
        if (empty($metrics)) return '';
        $cells = '';
        $count = 0;
        foreach (array_slice($metrics, 0, 4) as $metric) {
            if (!is_array($metric)) continue;
            $count++;
            $fill = $count === 3 ? $this->teal : $this->navy;
            $cells .= '<w:tc><w:tcPr><w:tcW w:w="2375" w:type="dxa"/><w:shd w:fill="' . $fill . '"/></w:tcPr>';
            $cells .= $this->paragraph((string)($metric['value'] ?? ''), '', 'center', true, 32, 'FFFFFF');
            $cells .= $this->paragraph((string)($metric['label'] ?? ''), '', 'center', false, 16, 'FFFFFF');
            $cells .= '</w:tc>';
        }
        while ($count < 4) {
            $count++;
            $cells .= '<w:tc><w:tcPr><w:tcW w:w="2375" w:type="dxa"/><w:shd w:fill="' . $this->navy . '"/></w:tcPr>' . $this->paragraph('-', '', 'center', true, 32, 'FFFFFF') . '</w:tc>';
        }
        return '<w:tbl><w:tblPr><w:tblW w:w="9500" w:type="dxa"/><w:tblBorders>' . $this->borders('FFFFFF') . '</w:tblBorders></w:tblPr><w:tblGrid><w:gridCol w:w="2375"/><w:gridCol w:w="2375"/><w:gridCol w:w="2375"/><w:gridCol w:w="2375"/></w:tblGrid><w:tr>' . $cells . '</w:tr></w:tbl>' . $this->spacer(8);
    }

    private function indicatorTable(array $metrics): string
    {
        $rows = [];
        foreach ($metrics as $metric) {
            if (is_array($metric)) {
                $rows[] = [(string)($metric['label'] ?? ''), (string)($metric['value'] ?? '')];
            }
        }
        if (empty($rows)) $rows[] = ['Sem indicadores', '-'];
        return $this->simpleTable(['Indicador', 'Valor'], $rows);
    }

    private function simpleTable(array $headers, array $rows): string
    {
        $colCount = max(1, count($headers));
        $width = (int)floor(9500 / $colCount);
        $grid = '';
        foreach ($headers as $_) $grid .= '<w:gridCol w:w="' . $width . '"/>';
        $xml = '<w:tbl><w:tblPr><w:tblStyle w:val="TableGrid"/><w:tblW w:w="9500" w:type="dxa"/><w:tblBorders>' . $this->borders($this->border) . '</w:tblBorders></w:tblPr><w:tblGrid>' . $grid . '</w:tblGrid>';
        $xml .= '<w:tr>';
        foreach ($headers as $head) {
            $xml .= $this->cell((string)$head, $this->navy, 'FFFFFF', true, $width);
        }
        $xml .= '</w:tr>';
        foreach ($rows as $row) {
            $xml .= '<w:tr>';
            for ($i = 0; $i < $colCount; $i++) {
                $xml .= $this->cell((string)($row[$i] ?? ''), 'FFFFFF', '222222', false, $width);
            }
            $xml .= '</w:tr>';
        }
        return $xml . '</w:tbl>' . $this->spacer(6);
    }

    private function cell(string $text, string $fill, string $color, bool $bold, int $width = 4750): string
    {
        if (preg_match('/^█+$/u', $text)) {
            $color = $this->teal;
            $bold = true;
        }
        return '<w:tc><w:tcPr><w:tcW w:w="' . (int)$width . '" w:type="dxa"/><w:shd w:fill="' . $fill . '"/></w:tcPr>' . $this->blockParagraphs($text, 18, $color, $bold, 'center') . '</w:tc>';
    }

    private function signatureBlock(string $subject): string
    {
        $subject = $this->cleanPersonName($subject);
        $xml = $this->spacer(24);
        $xml .= $this->paragraph('________________________________________', '', 'center', false, 20, '333333');
        $xml .= $this->paragraph($subject, '', 'center', true, 20, '333333');
        $xml .= $this->paragraph('Profissional responsável', '', 'center', false, 18, '666666');
        return $xml;
    }

    private function callout(string $title, string $text, string $fill, string $accent): string
    {
        $xml = '<w:tbl><w:tblPr><w:tblW w:w="9500" w:type="dxa"/><w:tblBorders>' . $this->borders($accent) . '</w:tblBorders></w:tblPr><w:tblGrid><w:gridCol w:w="9500"/></w:tblGrid><w:tr><w:tc><w:tcPr><w:tcW w:w="9500" w:type="dxa"/><w:shd w:fill="' . $fill . '"/></w:tcPr>';
        $xml .= $this->paragraph($title, '', 'left', true, 20, $this->navy);
        $xml .= $this->blockParagraphs($text, 18);
        $xml .= '</w:tc></w:tr></w:tbl>' . $this->spacer(8);
        return $xml;
    }

    private function decorativeStripes(): string
    {
        $cells = '';
        for ($i = 0; $i < 7; $i++) {
            $cells .= '<w:tc><w:tcPr><w:tcW w:w="170" w:type="dxa"/><w:shd w:fill="' . ($i % 2 === 0 ? $this->blue : '8CC2FF') . '"/></w:tcPr>' . $this->paragraph('', '', 'center', false, 4, 'FFFFFF') . '</w:tc>';
        }
        return '<w:tbl><w:tblPr><w:tblW w:w="1190" w:type="dxa"/><w:jc w:val="left"/><w:tblBorders>' . $this->borders('FFFFFF') . '</w:tblBorders></w:tblPr><w:tblGrid><w:gridCol w:w="170"/><w:gridCol w:w="170"/><w:gridCol w:w="170"/><w:gridCol w:w="170"/><w:gridCol w:w="170"/><w:gridCol w:w="170"/><w:gridCol w:w="170"/></w:tblGrid><w:tr>' . $cells . '</w:tr></w:tbl>';
    }

    private function brandMark(int $size = 24, string $align = 'left'): string
    {
        return $this->paragraph('◆ G4F', '', $align, true, $size, $this->navy);
    }

    private function heading(string $text, int $level): string
    {
        $style = $level === 1 ? 'Heading1' : 'Heading2';
        $size = $level === 1 ? 34 : 26;
        $color = $level === 1 ? $this->navy : $this->blue;
        return $this->paragraph($text, $style, 'left', false, $size, $color);
    }

    private function blockParagraphs(string $text, int $size = 22, string $color = '333333', bool $bold = false, string $align = 'left'): string
    {
        $text = TextNormalizer::clean($text, false);
        if ($text === '') {
            return $this->paragraph('', '', $align, $bold, $size, $color);
        }
        $out = [];
        foreach (preg_split('/\n+/', $text) as $line) {
            $line = trim($line);
            if ($line === '') continue;
            if (preg_match('/^[•\-*]\s*(.+)$/u', $line, $m)) {
                $out[] = $this->paragraph('• ' . $m[1], '', $align, $bold, $size, $color);
            } else {
                $out[] = $this->paragraph($line, '', $align, $bold, $size, $color);
            }
        }
        return implode('', $out);
    }

    private function paragraph(string $text, string $style = '', string $align = 'left', bool $bold = false, int $size = 22, string $color = '333333'): string
    {
        $pPr = '';
        if ($style !== '') {
            $pPr .= '<w:pStyle w:val="' . $this->x($style) . '"/>';
        }
        if ($align !== '') {
            $pPr .= '<w:jc w:val="' . $this->x($align) . '"/>';
        }
        $pPr .= '<w:spacing w:after="120" w:line="276" w:lineRule="auto"/>';
        $rPr = '<w:rPr>' . ($bold ? '<w:b/>' : '') . '<w:sz w:val="' . (int)$size . '"/><w:szCs w:val="' . (int)$size . '"/><w:color w:val="' . $this->x($color) . '"/></w:rPr>';
        return '<w:p><w:pPr>' . $pPr . '</w:pPr><w:r>' . $rPr . '<w:t xml:space="preserve">' . $this->x($text) . '</w:t></w:r></w:p>';
    }

    private function spacer(int $after): string
    {
        return '<w:p><w:pPr><w:spacing w:after="' . (int)($after * 20) . '"/></w:pPr></w:p>';
    }

    private function pageBreak(): string
    {
        // pageBreakBefore is more consistently honored by LibreOffice/Word in
        // documents generated into a pre-authored letterhead template than a
        // bare w:br page break.
        return '<w:p><w:pPr><w:pageBreakBefore/></w:pPr></w:p>';
    }

    private function borders(string $color): string
    {
        return '<w:top w:val="single" w:sz="4" w:space="0" w:color="' . $color . '"/><w:left w:val="single" w:sz="4" w:space="0" w:color="' . $color . '"/><w:bottom w:val="single" w:sz="4" w:space="0" w:color="' . $color . '"/><w:right w:val="single" w:sz="4" w:space="0" w:color="' . $color . '"/><w:insideH w:val="single" w:sz="4" w:space="0" w:color="' . $color . '"/><w:insideV w:val="single" w:sz="4" w:space="0" w:color="' . $color . '"/>';
    }

    private function letterheadSectPr(): string
    {
        // Relationship IDs match the bundled papel_timbrado_G4F.docx package.
        // No w:titlePg is set, so Word uses the default header containing the
        // full letterhead/background on the first and subsequent pages.
        return '<w:sectPr>'
            . '<w:headerReference w:type="even" r:id="rId11"/>'
            . '<w:headerReference w:type="default" r:id="rId12"/>'
            . '<w:footerReference w:type="default" r:id="rId13"/>'
            . '<w:headerReference w:type="first" r:id="rId14"/>'
            . '<w:pgSz w:w="11906" w:h="16838" w:code="9"/>'
            . '<w:pgMar w:top="1560" w:right="1134" w:bottom="1560" w:left="1134" w:header="709" w:footer="709" w:gutter="0"/>'
            . '<w:cols w:space="708"/><w:docGrid w:linePitch="360"/>'
            . '</w:sectPr>';
    }

    private function headerXml(): string
    {
        $xml = '<w:tbl><w:tblPr><w:tblW w:w="9500" w:type="dxa"/><w:tblBorders><w:bottom w:val="single" w:sz="6" w:space="0" w:color="' . $this->light . '"/></w:tblBorders></w:tblPr><w:tblGrid><w:gridCol w:w="4750"/><w:gridCol w:w="4750"/></w:tblGrid><w:tr>';
        $xml .= '<w:tc><w:tcPr><w:tcW w:w="4750" w:type="dxa"/></w:tcPr>' . $this->brandMark(22, 'left') . '</w:tc>';
        $xml .= '<w:tc><w:tcPr><w:tcW w:w="4750" w:type="dxa"/></w:tcPr>' . $this->paragraph('Relatórios G4F', '', 'right', false, 16, '666666') . '</w:tc>';
        $xml .= '</w:tr></w:tbl>';
        return '<?xml version="1.0" encoding="UTF-8" standalone="yes"?><w:hdr xmlns:w="http://schemas.openxmlformats.org/wordprocessingml/2006/main">' . $xml . '</w:hdr>';
    }

    private function footerXml(string $footer): string
    {
        $footer = trim($footer) ?: 'Brasília - DF, 70712-900 | SCN Q 2 BL A - Asa Norte, Corporate Financial Center | contato@g4f.com.br | www.g4f.com.br';
        return '<?xml version="1.0" encoding="UTF-8" standalone="yes"?><w:ftr xmlns:w="http://schemas.openxmlformats.org/wordprocessingml/2006/main"><w:p><w:pPr><w:jc w:val="center"/></w:pPr><w:r><w:rPr><w:sz w:val="14"/><w:color w:val="666666"/></w:rPr><w:t xml:space="preserve">' . $this->x($footer) . '</w:t></w:r></w:p></w:ftr>';
    }

    private function documentRelsXml(): string
    {
        return '<?xml version="1.0" encoding="UTF-8" standalone="yes"?><Relationships xmlns="http://schemas.openxmlformats.org/package/2006/relationships"><Relationship Id="rIdHeader" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/header" Target="header1.xml"/><Relationship Id="rIdFooter" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/footer" Target="footer1.xml"/></Relationships>';
    }

    private function rootRelsXml(): string
    {
        return '<?xml version="1.0" encoding="UTF-8" standalone="yes"?><Relationships xmlns="http://schemas.openxmlformats.org/package/2006/relationships"><Relationship Id="rId1" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/officeDocument" Target="word/document.xml"/><Relationship Id="rId2" Type="http://schemas.openxmlformats.org/package/2006/relationships/metadata/core-properties" Target="docProps/core.xml"/><Relationship Id="rId3" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/extended-properties" Target="docProps/app.xml"/></Relationships>';
    }

    private function contentTypesXml(): string
    {
        return '<?xml version="1.0" encoding="UTF-8" standalone="yes"?><Types xmlns="http://schemas.openxmlformats.org/package/2006/content-types"><Default Extension="rels" ContentType="application/vnd.openxmlformats-package.relationships+xml"/><Default Extension="xml" ContentType="application/xml"/><Override PartName="/word/document.xml" ContentType="application/vnd.openxmlformats-officedocument.wordprocessingml.document.main+xml"/><Override PartName="/word/styles.xml" ContentType="application/vnd.openxmlformats-officedocument.wordprocessingml.styles+xml"/><Override PartName="/word/settings.xml" ContentType="application/vnd.openxmlformats-officedocument.wordprocessingml.settings+xml"/><Override PartName="/word/header1.xml" ContentType="application/vnd.openxmlformats-officedocument.wordprocessingml.header+xml"/><Override PartName="/word/footer1.xml" ContentType="application/vnd.openxmlformats-officedocument.wordprocessingml.footer+xml"/><Override PartName="/docProps/core.xml" ContentType="application/vnd.openxmlformats-package.core-properties+xml"/><Override PartName="/docProps/app.xml" ContentType="application/vnd.openxmlformats-officedocument.extended-properties+xml"/></Types>';
    }

    private function stylesXml(): string
    {
        return '<?xml version="1.0" encoding="UTF-8" standalone="yes"?><w:styles xmlns:w="http://schemas.openxmlformats.org/wordprocessingml/2006/main"><w:docDefaults><w:rPrDefault><w:rPr><w:rFonts w:ascii="Arial" w:hAnsi="Arial" w:cs="Arial"/><w:sz w:val="22"/></w:rPr></w:rPrDefault></w:docDefaults><w:style w:type="paragraph" w:default="1" w:styleId="Normal"><w:name w:val="Normal"/><w:qFormat/></w:style><w:style w:type="paragraph" w:styleId="Title"><w:name w:val="Title"/><w:basedOn w:val="Normal"/><w:qFormat/><w:pPr><w:jc w:val="center"/><w:spacing w:after="240"/></w:pPr><w:rPr><w:b/><w:sz w:val="48"/><w:color w:val="' . $this->navy . '"/></w:rPr></w:style><w:style w:type="paragraph" w:styleId="Heading1"><w:name w:val="heading 1"/><w:basedOn w:val="Normal"/><w:next w:val="Normal"/><w:qFormat/><w:pPr><w:spacing w:before="240" w:after="120"/></w:pPr><w:rPr><w:sz w:val="34"/><w:color w:val="' . $this->navy . '"/></w:rPr></w:style><w:style w:type="paragraph" w:styleId="Heading2"><w:name w:val="heading 2"/><w:basedOn w:val="Normal"/><w:next w:val="Normal"/><w:qFormat/><w:pPr><w:spacing w:before="180" w:after="80"/></w:pPr><w:rPr><w:b/><w:sz w:val="26"/><w:color w:val="' . $this->blue . '"/></w:rPr></w:style></w:styles>';
    }

    private function settingsXml(): string
    {
        return '<?xml version="1.0" encoding="UTF-8" standalone="yes"?><w:settings xmlns:w="http://schemas.openxmlformats.org/wordprocessingml/2006/main"><w:zoom w:percent="100"/><w:defaultTabStop w:val="708"/></w:settings>';
    }

    private function coreXml(array $draft): string
    {
        $title = (string)($draft['report_title'] ?? 'Relatório G4F');
        $created = date('c');
        return '<?xml version="1.0" encoding="UTF-8" standalone="yes"?><cp:coreProperties xmlns:cp="http://schemas.openxmlformats.org/package/2006/metadata/core-properties" xmlns:dc="http://purl.org/dc/elements/1.1/" xmlns:dcterms="http://purl.org/dc/terms/" xmlns:dcmitype="http://purl.org/dc/dcmitype/" xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance"><dc:title>' . $this->x($title) . '</dc:title><dc:creator>Relatórios G4F</dc:creator><cp:lastModifiedBy>Relatórios G4F</cp:lastModifiedBy><dcterms:created xsi:type="dcterms:W3CDTF">' . $created . '</dcterms:created><dcterms:modified xsi:type="dcterms:W3CDTF">' . $created . '</dcterms:modified></cp:coreProperties>';
    }

    private function appXml(): string
    {
        return '<?xml version="1.0" encoding="UTF-8" standalone="yes"?><Properties xmlns="http://schemas.openxmlformats.org/officeDocument/2006/extended-properties" xmlns:vt="http://schemas.openxmlformats.org/officeDocument/2006/docPropsVTypes"><Application>Relatórios G4F</Application><DocSecurity>0</DocSecurity><ScaleCrop>false</ScaleCrop><Company>G4F</Company><LinksUpToDate>false</LinksUpToDate><SharedDoc>false</SharedDoc><HyperlinksChanged>false</HyperlinksChanged><AppVersion>1.0.0</AppVersion></Properties>';
    }

    private function templateLabel(string $template): string
    {
        return [
            'operational' => 'Operacional diário/mensal',
            'formal_managerial' => 'Gerencial formal',
            'project_technical' => 'Técnico/projeto',
            'annex_full' => 'Anexo evidencial completo',
        ][$template] ?? 'Operacional diário/mensal';
    }

    private function sourceLabel(string $source): string
    {
        return [
            'ticket' => 'Chamado',
            'ticket_task' => 'Tarefa de chamado',
            'ticket_followup' => 'Acompanhamento de chamado',
            'ticket_solution' => 'Solução de chamado',
            'project' => 'Projeto',
            'project_task' => 'Tarefa de projeto',
            'manual' => 'Entrada manual',
        ][$source] ?? $source;
    }

    private function nativeChart(string $type, string $title, array $rows, int $cx = 7600, int $cy = 3200): string
    {
        $rows = array_values(array_filter(array_slice($rows, 0, 20), static fn($r) => is_array($r) && isset($r['label']) && is_numeric($r['value'] ?? null)));
        if (empty($rows)) return '';
        $this->chartIndex++;
        $idx = $this->chartIndex;
        $relId = 'rIdG4FChart' . $idx;
        $path = 'word/charts/g4fchart' . $idx . '.xml';
        $this->chartParts[$relId] = ['path' => $path, 'xml' => $this->chartXml($type, $title, $rows, $idx)];
        $drawingId = 9000 + $idx;
        $cxEmu = max(3600000, $cx * 635);
        $cyEmu = max(2200000, $cy * 635);
        return '<w:p><w:pPr><w:jc w:val="center"/><w:spacing w:after="160"/></w:pPr><w:r><w:drawing><wp:inline distT="0" distB="0" distL="0" distR="0"><wp:extent cx="' . $cxEmu . '" cy="' . $cyEmu . '"/><wp:effectExtent l="0" t="0" r="0" b="0"/><wp:docPr id="' . $drawingId . '" name="' . $this->x($title) . '"/><wp:cNvGraphicFramePr/><a:graphic><a:graphicData uri="http://schemas.openxmlformats.org/drawingml/2006/chart"><c:chart r:id="' . $relId . '"/></a:graphicData></a:graphic></wp:inline></w:drawing></w:r></w:p>';
    }

    private function chartXml(string $type, string $title, array $rows, int $idx): string
    {
        $type = in_array($type, ['line','bar','column','pie','doughnut'], true) ? $type : 'bar';
        $series = $this->chartSeriesXml($title, $rows, $type);
        $ax1 = 700000 + ($idx * 10);
        $ax2 = $ax1 + 1;
        if ($type === 'pie' || $type === 'doughnut') {
            $tag = $type === 'doughnut' ? 'doughnutChart' : 'pieChart';
            $extra = $type === 'doughnut' ? '<c:holeSize val="58"/>' : '<c:firstSliceAng val="0"/>';
            $plot = '<c:' . $tag . '><c:varyColors val="1"/>' . $series . $extra . '</c:' . $tag . '>';
        } elseif ($type === 'line') {
            $plot = '<c:lineChart><c:grouping val="standard"/>' . $series . '<c:marker val="1"/><c:smooth val="0"/><c:axId val="' . $ax1 . '"/><c:axId val="' . $ax2 . '"/></c:lineChart>' . $this->axesXml($ax1, $ax2);
        } else {
            $dir = $type === 'column' ? 'col' : 'bar';
            $plot = '<c:barChart><c:barDir val="' . $dir . '"/><c:grouping val="clustered"/><c:varyColors val="1"/>' . $series . '<c:gapWidth val="120"/><c:axId val="' . $ax1 . '"/><c:axId val="' . $ax2 . '"/></c:barChart>' . $this->axesXml($ax1, $ax2);
        }
        return '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>'
            . '<c:chartSpace xmlns:c="http://schemas.openxmlformats.org/drawingml/2006/chart" xmlns:a="http://schemas.openxmlformats.org/drawingml/2006/main" xmlns:r="http://schemas.openxmlformats.org/officeDocument/2006/relationships">'
            . '<c:lang val="pt-BR"/><c:roundedCorners val="0"/><c:chart>'
            . $this->chartTitleXml($title)
            . '<c:plotArea><c:layout/>' . $plot . '</c:plotArea>'
            . '<c:legend><c:legendPos val="r"/><c:layout/></c:legend><c:plotVisOnly val="1"/><c:dispBlanksAs val="gap"/>'
            . '</c:chart></c:chartSpace>';
    }

    private function chartTitleXml(string $title): string
    {
        return '<c:title><c:tx><c:rich><a:bodyPr/><a:lstStyle/><a:p><a:r><a:rPr lang="pt-BR" b="1" sz="1400"><a:solidFill><a:srgbClr val="' . $this->navy . '"/></a:solidFill></a:rPr><a:t>' . $this->x($title) . '</a:t></a:r></a:p></c:rich></c:tx><c:layout/></c:title>';
    }

    private function chartSeriesXml(string $seriesName, array $rows, string $type): string
    {
        $labels = '';
        $values = '';
        $dpts = '';
        $palette = [$this->blue, $this->teal, $this->navy, '8CC2FF', 'F7B731', '4CAF50', 'EF5350', '9AA6B2', '7E57C2', '26A69A'];
        foreach ($rows as $i => $row) {
            $labels .= '<c:pt idx="' . $i . '"><c:v>' . $this->x((string)($row['label'] ?? 'N/I')) . '</c:v></c:pt>';
            $values .= '<c:pt idx="' . $i . '"><c:v>' . $this->chartNumber((float)($row['value'] ?? 0)) . '</c:v></c:pt>';
            $color = $palette[$i % count($palette)];
            $dpts .= '<c:dPt><c:idx val="' . $i . '"/><c:spPr><a:solidFill><a:srgbClr val="' . $color . '"/></a:solidFill><a:ln><a:solidFill><a:srgbClr val="FFFFFF"/></a:solidFill></a:ln></c:spPr></c:dPt>';
        }
        $count = count($rows);
        $lineStyle = '<c:spPr><a:ln w="25400"><a:solidFill><a:srgbClr val="' . $this->blue . '"/></a:solidFill></a:ln></c:spPr><c:marker><c:symbol val="circle"/><c:size val="6"/></c:marker>';
        return '<c:ser><c:idx val="0"/><c:order val="0"/><c:tx><c:v>' . $this->x($seriesName) . '</c:v></c:tx>'
            . ($type === 'line' ? $lineStyle : $dpts)
            . '<c:cat><c:strRef><c:f>G4F!$A$2:$A$' . ($count + 1) . '</c:f><c:strCache><c:ptCount val="' . $count . '"/>' . $labels . '</c:strCache></c:strRef></c:cat>'
            . '<c:val><c:numRef><c:f>G4F!$B$2:$B$' . ($count + 1) . '</c:f><c:numCache><c:formatCode>General</c:formatCode><c:ptCount val="' . $count . '"/>' . $values . '</c:numCache></c:numRef></c:val>'
            . '</c:ser>';
    }

    private function axesXml(int $catAx, int $valAx): string
    {
        return '<c:catAx><c:axId val="' . $catAx . '"/><c:scaling><c:orientation val="minMax"/></c:scaling><c:delete val="0"/><c:axPos val="b"/><c:tickLblPos val="nextTo"/><c:crossAx val="' . $valAx . '"/><c:crosses val="autoZero"/><c:auto val="1"/><c:lblAlgn val="ctr"/><c:lblOffset val="100"/></c:catAx>'
            . '<c:valAx><c:axId val="' . $valAx . '"/><c:scaling><c:orientation val="minMax"/></c:scaling><c:delete val="0"/><c:axPos val="l"/><c:majorGridlines/><c:numFmt formatCode="General" sourceLinked="1"/><c:tickLblPos val="nextTo"/><c:crossAx val="' . $catAx . '"/><c:crosses val="autoZero"/><c:crossBetween val="between"/></c:valAx>';
    }

    private function chartNumber(float $number): string
    {
        if (abs($number - round($number)) < 0.0001) return (string)(int)round($number);
        return rtrim(rtrim(number_format($number, 4, '.', ''), '0'), '.');
    }

    private function resetCharts(): void
    {
        $this->chartParts = [];
        $this->chartIndex = 0;
    }

    private function attachChartParts(array &$entries): void
    {
        if (empty($this->chartParts)) {
            return;
        }
        foreach ($this->chartParts as $relId => $part) {
            $entries[(string)$part['path']] = (string)$part['xml'];
        }

        $rels = (string)($entries['word/_rels/document.xml.rels'] ?? $this->documentRelsXml());
        foreach ($this->chartParts as $relId => $part) {
            if (strpos($rels, 'Id="' . $relId . '"') !== false) {
                continue;
            }
            $target = preg_replace('#^word/#', '', (string)$part['path']);
            $relationship = '<Relationship Id="' . $relId . '" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/chart" Target="' . $target . '"/>';
            $rels = str_replace('</Relationships>', $relationship . '</Relationships>', $rels);
        }
        $entries['word/_rels/document.xml.rels'] = $rels;

        $types = (string)($entries['[Content_Types].xml'] ?? $this->contentTypesXml());
        foreach ($this->chartParts as $part) {
            $partName = '/' . (string)$part['path'];
            if (strpos($types, 'PartName="' . $partName . '"') !== false) {
                continue;
            }
            $override = '<Override PartName="' . $partName . '" ContentType="application/vnd.openxmlformats-officedocument.drawingml.chart+xml"/>';
            $types = str_replace('</Types>', $override . '</Types>', $types);
        }
        $entries['[Content_Types].xml'] = $types;
    }

    private function zipRead(string $binary): array
    {
        $entries = [];
        $eocdSig = "\x50\x4b\x05\x06";
        $eocd = strrpos($binary, $eocdSig);
        if ($eocd === false || $eocd + 22 > strlen($binary)) {
            return [];
        }
        $e = unpack('Vsig/vdisk/vcdStartDisk/vdiskEntries/ventries/VcdSize/VcdOffset/vcommentLen', substr($binary, $eocd, 22));
        if (!is_array($e) || (int)$e['sig'] !== 0x06054b50) {
            return [];
        }
        $offset = (int)$e['cdOffset'];
        $count = (int)$e['entries'];
        for ($i = 0; $i < $count; $i++) {
            if ($offset + 46 > strlen($binary)) break;
            $h = unpack('Vsig/vverMade/vverNeed/vflag/vmethod/vtime/vdate/Vcrc/Vcsize/Vusize/vnlen/velen/vclen/vdisk/vintAttr/VextAttr/VlocalOffset', substr($binary, $offset, 46));
            if (!is_array($h) || (int)$h['sig'] !== 0x02014b50) break;
            $nameLen = (int)$h['nlen'];
            $extraLen = (int)$h['elen'];
            $commentLen = (int)$h['clen'];
            $name = substr($binary, $offset + 46, $nameLen);
            $localOffset = (int)$h['localOffset'];
            $method = (int)$h['method'];
            $csize = (int)$h['csize'];

            if ($name !== '' && substr($name, -1) !== '/' && $localOffset + 30 <= strlen($binary)) {
                $lh = unpack('Vsig/vver/vflag/vmethod/vtime/vdate/Vcrc/Vcsize/Vusize/vnlen/velen', substr($binary, $localOffset, 30));
                if (is_array($lh) && (int)$lh['sig'] === 0x04034b50) {
                    $dataStart = $localOffset + 30 + (int)$lh['nlen'] + (int)$lh['elen'];
                    $data = substr($binary, $dataStart, $csize);
                    if ($method === 0) {
                        $entries[$name] = $data;
                    } elseif ($method === 8) {
                        $inflated = @gzinflate($data);
                        if (is_string($inflated)) {
                            $entries[$name] = $inflated;
                        }
                    }
                }
            }
            $offset += 46 + $nameLen + $extraLen + $commentLen;
        }
        return $entries;
    }

    private function zipStore(array $files): string
    {
        $local = '';
        $central = '';
        $offset = 0;
        $count = 0;
        foreach ($files as $name => $content) {
            $name = str_replace('\\', '/', (string)$name);
            $content = (string)$content;
            $crc = crc32($content);
            if ($crc < 0) {
                $crc += 4294967296;
            }
            $size = strlen($content);
            $time = 0;
            $date = 0;
            $nameLen = strlen($name);
            $localHeader = pack('VvvvvvVVVvv', 0x04034b50, 20, 0, 0, $time, $date, $crc, $size, $size, $nameLen, 0) . $name;
            $local .= $localHeader . $content;
            $central .= pack('VvvvvvvVVVvvvvvVV', 0x02014b50, 20, 20, 0, 0, $time, $date, $crc, $size, $size, $nameLen, 0, 0, 0, 0, 0, $offset) . $name;
            $offset += strlen($localHeader) + $size;
            $count++;
        }
        $end = pack('VvvvvVVv', 0x06054b50, 0, 0, $count, $count, strlen($central), strlen($local), 0);
        return $local . $central . $end;
    }

    private function x($value): string
    {
        return htmlspecialchars((string)$value, ENT_XML1 | ENT_COMPAT, 'UTF-8');
    }
}
