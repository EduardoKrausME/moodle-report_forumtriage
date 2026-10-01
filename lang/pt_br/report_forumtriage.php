<?php
// This file is part of Moodle - http://moodle.org/
//
// Moodle is free software: you can redistribute it and/or modify
// it under the terms of the GNU General Public License as published by
// the Free Software Foundation, either version 3 of the License, or
// (at your option) any later version.
//
// Moodle is distributed in the hope that it will be useful,
// but WITHOUT ANY WARRANTY; without even the implied warranty of
// MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
// GNU General Public License for more details.
//
// You should have received a copy of the GNU General Public License
// along with Moodle.  If not, see <http://www.gnu.org/licenses/>.

/**
 * Brazilian Portuguese language strings.
 *
 * @package    report_forumtriage
 * @copyright  2026 Eduardo Kraus
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die;

$string['aianalysed'] = 'A IA analisou {$a->analysed} de {$a->candidates} candidatos semânticos.';
$string['aiunavailable'] = 'A análise semântica não pôde ser concluída. Os resultados determinísticos continuam disponíveis.';
$string['allforums'] = 'Todos os fóruns';
$string['allgroups'] = 'Todos os grupos visíveis';
$string['authors'] = '{$a} autores';
$string['confidence:high'] = 'Confiança alta';
$string['confidence:low'] = 'Confiança baixa';
$string['confidence:medium'] = 'Confiança média';
$string['deterministicnotice'] = 'A visão inicial é determinística. Envie os filtros para executar a triagem semântica pelo AI Bridge.';
$string['filter:forum'] = 'Fórum';
$string['filter:group'] = 'Grupo';
$string['filter:onlynoteacher'] = 'Apenas discussões sem resposta docente';
$string['filter:period'] = 'Período de atividade';
$string['filter:recentonly'] = 'Apenas questões recentes';
$string['forum'] = 'Fórum: {$a}';
$string['forumtriage:view'] = 'Visualizar relatório de triagem de fóruns';
$string['group'] = 'Grupo: {$a}';
$string['invalidairesponse'] = 'A IA retornou uma resposta estruturada inválida. Os resultados determinísticos continuam disponíveis.';
$string['lastactivity'] = 'Última atividade: {$a}';
$string['mostdiscussed'] = 'Assuntos mais discutidos';
$string['needsattention'] = 'Precisa de atenção';
$string['noaiyet'] = 'Execute a triagem para gerar as seções semânticas, como possivelmente não resolvido e dúvidas recorrentes.';
$string['noclusters'] = 'Nenhum agrupamento semântico recorrente foi identificado nas discussões analisadas.';
$string['noresponse'] = 'Sem resposta';
$string['noteacherreply'] = 'Sem resposta docente';
$string['nothreads'] = 'Nenhuma discussão visível corresponde aos filtros selecionados.';
$string['opendiscussion'] = 'Abrir discussão';
$string['period:all'] = 'Qualquer período';
$string['period:days'] = 'Últimos {$a} dia(s)';
$string['pluginname'] = 'Triagem de fóruns';
$string['possiblyunresolved'] = 'Possivelmente não resolvido';
$string['priority'] = 'Prioridade: {$a}';
$string['priority:high'] = 'Alta';
$string['priority:medium'] = 'Média';
$string['priority:normal'] = 'Normal';
$string['privacy:metadata'] = 'O relatório de triagem de fóruns não armazena dados pessoais. Ele lê, durante a requisição, dados já armazenados pelos fóruns do Moodle e não persiste prompts nem respostas brutas da IA.';
$string['recurringquestions'] = 'Dúvidas recorrentes';
$string['replies'] = '{$a} respostas';
$string['runtriage'] = 'Executar triagem';
$string['sectionempty'] = 'Nenhuma discussão nesta seção.';
$string['settings'] = 'Configurações da triagem de fóruns';
$string['settings:longthreadreplies'] = 'Respostas que definem uma thread longa';
$string['settings:longthreadreplies_desc'] = 'Uma thread com esta quantidade ou mais de respostas visíveis é marcada como longa.';
$string['settings:maxaithreads'] = 'Máximo de discussões por requisição de IA';
$string['settings:maxaithreads_desc'] = 'Limita os candidatos semânticos enviados ao AI Bridge. As demais discussões continuam aparecendo nas seções determinísticas.';
$string['settings:maxpostchars'] = 'Máximo de caracteres por postagem enviados à IA';
$string['settings:maxpostchars_desc'] = 'O texto da postagem é convertido para texto simples e truncado antes da requisição à IA.';
$string['settings:noteacherhours'] = 'Horas sem resposta docente para prioridade alta';
$string['settings:noteacherhours_desc'] = 'Limite objetivo calculado em PHP a partir da postagem inicial.';
$string['settings:recentdays'] = 'Janela de questões recentes em dias';
$string['settings:recentdays_desc'] = 'Usada pelo filtro “Apenas questões recentes”.';
$string['settings:unansweredhours'] = 'Horas para discussão sem resposta virar prioridade alta';
$string['settings:unansweredhours_desc'] = 'Limite objetivo calculado em PHP. A IA não pode substituir essa regra de prioridade.';
$string['stat:long'] = 'Threads longas';
$string['stat:noteacher'] = 'Sem resposta docente';
$string['stat:unanswered'] = 'Sem resposta';
$string['stat:visible'] = 'Discussões visíveis';
$string['teacherreplied'] = 'Professor respondeu';
