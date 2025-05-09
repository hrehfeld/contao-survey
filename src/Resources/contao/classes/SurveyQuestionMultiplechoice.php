<?php

declare(strict_types=1);

/*
 * @copyright  Helmut Schottmüller 2005-2018 <http://github.com/hschottm>
 * @author     Helmut Schottmüller (hschottm)
 * @package    contao-survey
 * @license    LGPL-3.0+, CC-BY-NC-3.0
 * @see	       https://github.com/hschottm/survey_ce
 *
 * forked by pdir
 * @author     Mathias Arzberger <develop@pdir.de>
 * @link       https://github.com/pdir/contao-survey
 */

namespace Hschottm\SurveyBundle;

use Contao\Database;
use Contao\FrontendTemplate;
use Contao\StringUtil;
use Hschottm\SurveyBundle\Export\Exporter;

/**
 * Class SurveyQuestionMultiplechoice.
 *
 * @copyright  Helmut Schottmüller 2009-2010
 * @author     Helmut Schottmüller <contao@aurealis.de>
 */
class SurveyQuestionMultiplechoice extends SurveyQuestion
{
    public const TYPE = 'multiplechoice';

    protected $choices = [];

    /**
     * @var array|null
     */
    protected $resultData;

    /**
     * Import String library.
     *
     * @param mixed $question_id
     */
    public function __construct($question_id = 0)
    {
        parent::__construct($question_id);
    }

    public function __set($name, $value): void
    {
        switch ($name) {
            default:
                parent::__set($name, $value);
                break;
        }
    }

    public function getResultData(): array
    {
        /*
         * an array of the form question.id => result, where result can be array or string|int
         * like
         *      [5 => "1", 6 => [2 => "1", 4 => "1"]]
         * where "1" means that the answer was selected,
         */
        if (null === $this->resultData) {
            $result = [];

            if (isset($this->statistics['cumulated']) && \is_array($this->statistics['cumulated'])) {
                $result['statistics'] = $this->statistics;

                $result['choices'] = $this->getQuestionChoices();

                $result['categories'] = [];
                $counter = 1;

                foreach ($result['choices'] as $id => $choice) {
                    $result['choices'][$id] = $choice;

                    $result['answers'][$counter] = [
                        'choices' => $choice['choice'],
                        'selections' => ($this->statistics['cumulated'][$id] ?? 0),
                    ];

                    if (isset($choice['category'])) {
                        $result['categories'][$choice['category']] =
                            ($result['categories'][$choice['category']] ?? 0) + ($this->statistics['cumulated'][$id] ?? 0);
                    }

                    ++$counter;
                }
            }
            $this->resultData = $result;
        }

        return $this->resultData;
    }

    public function getAnswersAsHTML()
    {
        if (!empty($resultData = $this->getResultData())) {
            $survey = SurveyModel::findByQuestionId((int) $this->id);

            $template = new FrontendTemplate('survey_answers_multiplechoice');
            $template->statistics = $resultData['statistics'];
            $template->summary = $GLOBALS['TL_LANG']['tl_survey_result']['cumulatedSummary'];
            $template->answer = $GLOBALS['TL_LANG']['tl_survey_result']['answer'];
            $template->nrOfSelections = $GLOBALS['TL_LANG']['tl_survey_result']['nrOfSelections'];
            $template->choices = $resultData['choices'];
            $template->other = $this->arrData['addother'] ? true : false;
            $template->othertitle = StringUtil::specialchars($this->arrData['othertitle']);
            $template->useCategories = ($survey && $survey->useResultCategories);
            $template->survey = $survey;
            $otherchoices = [];

            if (\count($this->statistics['cumulated']['other'])) {
                foreach ($this->statistics['cumulated']['other'] as $value) {
                    $key = StringUtil::specialchars($value);

                    if (\array_key_exists($key, $otherchoices)) {
                        ++$otherchoices[$key];
                    } else {
                        $otherchoices[$key] = 1;
                    }
                }
            }
            $template->otherchoices = $otherchoices;

            return $template->parse();
        }
    }

    public function exportDataToExcel(& $exporter, $sheet, & $row): void
    {
        $exporter->setCellValue($sheet, $row, 0, [Exporter::DATA => 'ID', Exporter::BGCOLOR => $this->titlebgcolor, Exporter::COLOR => $this->titlecolor, Exporter::FONTWEIGHT => Exporter::FONTWEIGHT_BOLD, Exporter::COLWIDTH => Exporter::COLWIDTH_AUTO]);
        $exporter->setCellValue($sheet, $row, 1, [Exporter::DATA => $this->id, Exporter::CELLTYPE => Exporter::CELLTYPE_FLOAT, Exporter::COLWIDTH => Exporter::COLWIDTH_AUTO]);
        ++$row;
        $exporter->setCellValue($sheet, $row, 0, [Exporter::DATA => $GLOBALS['TL_LANG']['tl_survey_question']['questiontype'][0], Exporter::BGCOLOR => $this->titlebgcolor, Exporter::COLOR => $this->titlecolor, Exporter::FONTWEIGHT => Exporter::FONTWEIGHT_BOLD]);
        $exporter->setCellValue($sheet, $row, 1, [Exporter::DATA => $GLOBALS['TL_LANG']['tl_survey_question'][$this->questiontype]]);
        ++$row;
        $exporter->setCellValue($sheet, $row, 0, [Exporter::DATA => $GLOBALS['TL_LANG']['tl_survey_question']['title'][0], Exporter::BGCOLOR => $this->titlebgcolor, Exporter::COLOR => $this->titlecolor, Exporter::FONTWEIGHT => Exporter::FONTWEIGHT_BOLD]);
        $exporter->setCellValue($sheet, $row, 1, [Exporter::DATA => $this->title]);
        ++$row;
        $exporter->setCellValue($sheet, $row, 0, [Exporter::DATA => $GLOBALS['TL_LANG']['tl_survey_question']['question'][0], Exporter::BGCOLOR => $this->titlebgcolor, Exporter::COLOR => $this->titlecolor, Exporter::FONTWEIGHT => Exporter::FONTWEIGHT_BOLD]);
        $exporter->setCellValue($sheet, $row, 1, [Exporter::DATA => strip_tags($this->question)]);
        ++$row;
        $exporter->setCellValue($sheet, $row, 0, [Exporter::DATA => $GLOBALS['TL_LANG']['tl_survey_question']['answered'], Exporter::BGCOLOR => $this->titlebgcolor, Exporter::COLOR => $this->titlecolor, Exporter::FONTWEIGHT => Exporter::FONTWEIGHT_BOLD]);
        $exporter->setCellValue($sheet, $row, 1, [Exporter::DATA => $this->statistics['answered'], Exporter::CELLTYPE => Exporter::CELLTYPE_FLOAT]);
        ++$row;
        $exporter->setCellValue($sheet, $row, 0, [Exporter::DATA => $GLOBALS['TL_LANG']['tl_survey_question']['skipped'], Exporter::BGCOLOR => $this->titlebgcolor, Exporter::COLOR => $this->titlecolor, Exporter::FONTWEIGHT => Exporter::FONTWEIGHT_BOLD]);
        $exporter->setCellValue($sheet, $row, 1, [Exporter::DATA => $this->statistics['skipped'], Exporter::CELLTYPE => Exporter::CELLTYPE_FLOAT]);
        ++$row;
        $exporter->setCellValue($sheet, $row, 0, [Exporter::DATA => $GLOBALS['TL_LANG']['tl_survey_question']['answers'], Exporter::BGCOLOR => $this->titlebgcolor, Exporter::COLOR => $this->titlecolor, Exporter::FONTWEIGHT => Exporter::FONTWEIGHT_BOLD]);
        $exporter->setCellValue($sheet, $row + 1, 0, [Exporter::DATA => $GLOBALS['TL_LANG']['tl_survey_result']['nrOfSelections'], Exporter::BGCOLOR => $this->titlebgcolor, Exporter::COLOR => $this->titlecolor, Exporter::FONTWEIGHT => Exporter::FONTWEIGHT_BOLD]);

        $arrChoices = $this->getQuestionChoices();

        $col = 2;

        foreach ($arrChoices as $id => $choice) {
            $exporter->setCellValue($sheet, $row, $col, [Exporter::DATA => $choice['choice']]);
            // pdir
            $data = \array_key_exists($id, (array) $this->statistics['cumulated']) ?
                $this->statistics['cumulated'][$id] :
                0;

            $exporter->setCellValue(
                $sheet,
                $row + 1,
                $col++,
                [
                    Exporter::DATA => $data,
                    Exporter::CELLTYPE => Exporter::CELLTYPE_FLOAT,
                ]
            );
        }

        if ($this->arrData['addother']) {
            $exporter->setCellValue($sheet, $row, $col, [Exporter::DATA => $this->arrData['othertitle']]);
            $exporter->setCellValue($sheet, $row + 1, $col++, [
                Exporter::DATA => \count(($this->statistics['cumulated']['other'] ?? [])),
                Exporter::CELLTYPE => Exporter::CELLTYPE_FLOAT,
            ]);

            if (!empty($this->statistics['cumulated']['other'])) {
                $otherchoices = [];

                foreach ($this->statistics['cumulated']['other'] as $value) {
                    if (\array_key_exists($value, $otherchoices)) {
                        ++$otherchoices[$value];
                    }
                }

                foreach ($otherchoices as $key => $count) {
                    $exporter->setCellValue($sheet, $row, $col, [Exporter::DATA => $key, Exporter::BGCOLOR => $this->otherbackground, Exporter::COLOR => $this->othercolor]);
                    $exporter->setCellValue($sheet, $row + 1, $col++, [Exporter::DATA => $count, Exporter::CELLTYPE => Exporter::CELLTYPE_FLOAT]);
                }
            }
        }
        $row += 3;
    }

    /**
     * Exports multiple choice question headers and all existing answers.
     *
     * Questions of subtype mc_dichotomous occupy one column and get yes/no values as answers.
     *
     * Questions of subtype mc_singleresponse also occupy one column only and the participants
     * choice as answer. If there is the optinal "other answer" present and choosen, the value
     * will be the participants input prepended by the title of the other answer.
     *
     * Questions of subtype mc_multipleresponse occupy one column for every choice.
     * All possible coices are given in the header (turned ccw) and 'x' will be the value, if
     * choosen by the participant. The optional "other answer" gets its own column with the
     * participants entry as value. Common question headers, e.g. the id, question-numbers,
     * title are exported in merged cells spanning all choice columns.
     *
     * As a side effect the width for each column is calculated and set via the given $expoerter object.
     * Row height is currently calculated/set ONLY for the row with subquestions/choices, which is turned
     * 90° ccw ... thus it is effectively also a text width calculation.
     *
     * Not setting row(/text) height explicitly in the general case is no problem in OpenOffice Calc 3.1,
     * which does a good job here by default. However Excel 95/97 seems to do it worse,
     * I can't test that currently. "Set optimal row height" might help users of Excel.
     *
     * @param object $exporter        instance of the Excel exporter object
     * @param string $sheet           name of the worksheet
     * @param int    $row             row to put a cell in
     * @param int    $col             col to put a cell in
     * @param array  $questionNumbers array with page and question numbers
     * @param array  $participants    array with all participant data
     *
     * @return array the cells to be added to the export
     */
    public function exportDetailsToExcel(& $exporter, $sheet, & $row, & $col, $questionNumbers, $participants)
    {
        $valueCol = $col;
        $rotateInfo = [];
        $headerCells = $this->exportQuestionHeadersToExcel($exporter, $sheet, $row, $col, $questionNumbers, $rotateInfo);
        $resultCells = $this->exportDetailResults($exporter, $sheet, $row, $valueCol, $participants);

        return array_merge($headerCells, $resultCells);
    }

    public function resultAsString($res)
    {
        $arrAnswer = StringUtil::deserialize($res, true);

        $arrChoices = $this->getQuestionChoices();

        if (\is_array($arrAnswer['value'])) {
            foreach ($arrAnswer['value'] as $key => $val) {
                $selections[] = $arrChoices[$val]['choice'];
            }

            return implode(', ', $selections);
        }

        if (is_numeric($arrAnswer['value'] ?? null)) {
            return $arrChoices[$arrAnswer['value']]['choice'];
        }

        if (!empty($arrAnswer['other'])) {
            return $arrAnswer['other'];
        }
    }

    protected function calculateStatistics(): void
    {
        if (\array_key_exists('id', $this->arrData) && \array_key_exists('parentID', $this->arrData)) {
            $objResult = Database::getInstance()->prepare('SELECT * FROM tl_survey_result WHERE qid=? AND pid=?')
                ->execute($this->arrData['id'], $this->arrData['parentID'])
            ;

            if ($objResult->numRows) {
                $this->calculateAnsweredSkipped($objResult);
                $this->calculateCumulated();
            }
        }
    }

    protected function calculateAnsweredSkipped(& $objResult): void
    {
        $this->arrStatistics = [];
        $this->arrStatistics['answered'] = 0;
        $this->arrStatistics['skipped'] = 0;

        while ($objResult->next()) {
            $id = !empty($objResult->pin) ? $objResult->pin : $objResult->uid;
            $this->arrStatistics['participants'][$id][] = $objResult->row();
            $this->arrStatistics['answers'][] = $objResult->result;

            if (!empty($objResult->result)) {
                $arrAnswer = StringUtil::deserialize($objResult->result, true);
                $found = false;

                if (\is_array($arrAnswer['value'])) {
                    foreach ($arrAnswer['value'] as $answervalue) {
                        if (!empty($answervalue)) {
                            $found = true;
                        }
                    }
                } else {
                    if (!empty($arrAnswer['value'])) {
                        $found = true;
                    }
                }

                if (!empty($arrAnswer['other'])) {
                    $found = true;
                }

                if ($found) {
                    ++$this->arrStatistics['answered'];
                } else {
                    ++$this->arrStatistics['skipped'];
                }
            } else {
                ++$this->arrStatistics['skipped'];
            }
        }
    }

    protected function calculateCumulated(): void
    {
        $cumulated = [];
        $cumulated['other'] = [];

        foreach ($this->arrStatistics['answers'] as $answer) {
            $arrAnswer = StringUtil::deserialize($answer, true);

            if (\is_array($arrAnswer['value'])) {
                foreach ($arrAnswer['value'] as $answervalue) {
                    if (!empty($answervalue)) {
                        $cumulated[$answervalue] = ($cumulated[$answervalue] ?? 0) + 1;
                    }
                }
            } else {
                if (!empty($arrAnswer['value'])) {
                    $cumulated[$arrAnswer['value']] = ($cumulated[$arrAnswer['value']] ?? 0) + 1;
                }
            }

            if (!empty($arrAnswer['other'])) {
                array_push($cumulated['other'], $arrAnswer['other']);
            }
        }
        $this->arrStatistics['cumulated'] = $cumulated;
    }

    /**
     * Exports the column headers for a question of type 'multiple choice'.
     *
     * Several rows are returned, so that the user of the Excel file is able to
     * use them for reference, filtering and sorting.
     *
     * @param object $exporter        instance of the Excel exporter object
     * @param string $sheet           name of the worksheet
     * @param int    $row             in/out row to put a cell in
     * @param int    $col             in/out col to put a cell in
     * @param array  $questionNumbers array with page and question numbers
     * @param array  $rotateInfo      out param with row => text for later calculation of row height
     *
     * @return array the cells to be added to the export
     */
    protected function exportQuestionHeadersToExcel(& $exporter, $sheet, & $row, & $col, $questionNumbers, & $rotateInfo)
    {
        $this->choices = 'mc_dichotomous' === $this->arrData['multiplechoice_subtype']
            ? [
                0 => $GLOBALS['TL_LANG']['tl_survey_question']['yes'],
                1 => $GLOBALS['TL_LANG']['tl_survey_question']['no'],
            ]
            : StringUtil::deserialize($this->arrData['choices'], true);

        if ($this->arrData['addother']) {
            $this->choices[] = preg_replace('/[-=>:\s]+$/', '', $this->arrData['othertitle']);
        }
        $numcols = 'mc_multipleresponse' === $this->arrData['multiplechoice_subtype'] ? \count($this->choices) : 1;

        $result = [];

        // ID and question numbers
        $data = [
            Exporter::DATA => $this->id,
            Exporter::CELLTYPE => Exporter::CELLTYPE_FLOAT,
        ];

        if ($numcols > 1) {
            $data[Exporter::MERGE] = $exporter->getCell($row, $col).':'.$exporter->getCell($row, $col + $numcols - 1);
        }
        $exporter->setCellValue($sheet, $row, $col, $data);

        ++$row;
        $data = [
            Exporter::DATA => $questionNumbers['abs_question_no'],
            Exporter::CELLTYPE => Exporter::CELLTYPE_FLOAT,
            Exporter::FONTSTYLE => Exporter::FONTSTYLE_ITALIC,
        ];

        if ($numcols > 1) {
            $data[Exporter::MERGE] = $exporter->getCell($row, $col).':'.$exporter->getCell($row, $col + $numcols - 1);
        }
        $exporter->setCellValue($sheet, $row, $col, $data);

        ++$row;
        $data = [
            Exporter::DATA => $questionNumbers['page_no'].'.'.$questionNumbers['rel_question_no'],
            Exporter::CELLTYPE => Exporter::CELLTYPE_FLOAT,
            Exporter::FONTWEIGHT => Exporter::FONTWEIGHT_BOLD,
            Exporter::ALIGNMENT => Exporter::ALIGNMENT_H_CENTER,
        ];

        if ($numcols > 1) {
            $data[Exporter::MERGE] = $exporter->getCell($row, $col).':'.$exporter->getCell($row, $col + $numcols - 1);
        }
        $exporter->setCellValue($sheet, $row, $col, $data);

        ++$row;

        // question type
        $data = [
            Exporter::DATA => $GLOBALS['TL_LANG']['tl_survey_question'][$this->questiontype].', '.
                $GLOBALS['TL_LANG']['tl_survey_question'][$this->arrData['multiplechoice_subtype']],
        ];

        if ($numcols > 1) {
            $data[Exporter::MERGE] = $exporter->getCell($row, $col).':'.$exporter->getCell($row, $col + $numcols - 1);
        }
        $exporter->setCellValue($sheet, $row, $col, $data);

        ++$row;

        // answered and skipped info, retrieves all answers as a side effect
        $data = [
            Exporter::DATA => $this->statistics['answered'],
            Exporter::CELLTYPE => Exporter::CELLTYPE_FLOAT,
        ];

        if ($numcols > 1) {
            $data[Exporter::MERGE] = $exporter->getCell($row, $col).':'.$exporter->getCell($row, $col + $numcols - 1);
        }
        $exporter->setCellValue($sheet, $row, $col, $data);

        ++$row;
        $data = [
            Exporter::DATA => $this->statistics['skipped'],
            Exporter::CELLTYPE => Exporter::CELLTYPE_FLOAT,
        ];

        if ($numcols > 1) {
            $data[Exporter::MERGE] = $exporter->getCell($row, $col).':'.$exporter->getCell($row, $col + $numcols - 1);
        }
        $exporter->setCellValue($sheet, $row, $col, $data);

        ++$row;

        // question title
        $data = [
            Exporter::DATA => StringUtil::decodeEntities($this->title).($this->arrData['obligatory'] ? ' *' : ''),
            Exporter::CELLTYPE => Exporter::CELLTYPE_STRING,
            Exporter::ALIGNMENT => Exporter::ALIGNMENT_H_CENTER,
            Exporter::TEXTWRAP => true,
        ];

        if ($numcols > 1) {
            $data[Exporter::MERGE] = $exporter->getCell($row, $col).':'.$exporter->getCell($row, $col + $numcols - 1);
        }
        $exporter->setCellValue($sheet, $row, $col, $data);

        ++$row;

        if (1 === $numcols) {
            $data = [
                Exporter::DATA => '',
                Exporter::ALIGNMENT => Exporter::ALIGNMENT_H_CENTER,
                Exporter::TEXTWRAP => true,
                Exporter::BORDERBOTTOM => Exporter::BORDER_THIN,
                Exporter::BORDERBOTTOMCOLOR => '#000000',
            ];
            $exporter->setCellValue($sheet, $row, $col, $data);

            ++$col;
        } else {
            // output all choice columns
            $rotateInfo[$row] = [];

            foreach ($this->choices as $key => $choice) {
                $data = [
                    Exporter::DATA => \is_array($choice) ? $choice['choice'] : $choice,
                    Exporter::ALIGNMENT => Exporter::ALIGNMENT_H_CENTER,
                    Exporter::TEXTWRAP => true,
                    Exporter::TEXTROTATE => $this->arrData['addother'] && ($key === \count($this->choices) - 1) ? Exporter::TEXTROTATE_NONE : Exporter::TEXTROTATE_COUNTERCLOCKWISE,
                    Exporter::BORDERBOTTOM => Exporter::BORDER_THIN,
                    Exporter::BORDERBOTTOMCOLOR => '#000000',
                ];
                $exporter->setCellValue($sheet, $row, $col, $data);
                ++$col;
            }
        }
        ++$row;

        return $result;
    }

    /**
     * Exports all results/answers to the question at hand.
     *
     * Sets some column widthes as a side effect.
     *
     * @param object $exporter     instance of the Excel exporter object
     * @param string $sheet        name of the worksheet
     * @param int    $row          row to put a cell in
     * @param int    $col          col to put a cell in
     * @param array  $participants array with all participant data
     *
     * @return array the cells to be added to the export
     *
     * @TODO: make alignment and max colwidth configurable in dcaconfig.php ?
     */
    protected function exportDetailResults(& $exporter, $sheet, & $row, & $col, $participants)
    {
        $cells = [];
        $startCol = $col;

        foreach (array_keys($participants) as $key) {
            $data = false;

            if (isset($this->statistics['participants']) && !empty($this->statistics['participants'][$key]['result'])) {
                // future state of survey_ce
                $data = $this->statistics['participants'][$key]['result'];
            } elseif (isset($this->statistics['participants']) && !empty($this->statistics['participants'][$key][0]['result'])) {
                // current state of survey_ce: additional subarray with always 1 entry
                $data = $this->statistics['participants'][$key][0]['result'];
            }

            if ($data) {
                $col = $startCol;
                $arrAnswers = StringUtil::deserialize($data, true);

                if ('mc_dichotomous' === $this->arrData['multiplechoice_subtype']) {
                    if ($arrAnswers['value'] !== '') {
                    $exporter->setCellValue($sheet, $row, $col, [
                        Exporter::DATA => $this->choices[$arrAnswers['value'] - 1],
                        Exporter::ALIGNMENT => Exporter::ALIGNMENT_H_CENTER,
                        Exporter::TEXTWRAP => true,
                    ]);
                    }
                } elseif ('mc_singleresponse' === $this->arrData['multiplechoice_subtype']) {
                    $emptyAnswer = false;

                    foreach ($this->choices as $choice) {
                        if (0 === empty($choice['choice'])) {
                            $emptyAnswer = true;
                            break
                        }
                    }
                    $strAnswer = ($emptyAnswer ? $arrAnswers['value'].' - ' : '').$this->choices[$arrAnswers['value']]['choice'];

                    if ($this->arrData['addother'] && ($arrAnswers['value'] === \count($this->choices))) {
                        $strAnswer .= ': '.StringUtil::decodeEntities($arrAnswers['other']);
                    }
                    $exporter->setCellValue($sheet, $row, $col, [
                        Exporter::DATA => $strAnswer,
                        Exporter::ALIGNMENT => Exporter::ALIGNMENT_H_CENTER,
                        Exporter::TEXTWRAP => true,
                    ]);
                } elseif ('mc_multipleresponse' === $this->arrData['multiplechoice_subtype']) {
                    foreach (array_keys($this->choices) as $k) {
                        $strAnswer = \is_array($arrAnswers['value']) && \array_key_exists($k + 1, $arrAnswers['value'])
                            ? $this->arrData['addother'] && ($k + 1 === \count($this->choices))
                                ? StringUtil::decodeEntities($arrAnswers['other'])
                                : 'x'
                            : '';

                        if (!empty($strAnswer)) {
                            $exporter->setCellValue($sheet, $row, $col, [
                                Exporter::DATA => $strAnswer,
                                Exporter::ALIGNMENT => Exporter::ALIGNMENT_H_CENTER,
                                Exporter::TEXTWRAP => true,
                            ]);
                        }
                        ++$col;
                    }
                }
            }
            ++$row;
        }

        return $cells;
    }

    /*public function resultAsString($res)
          {
              $arrAnswer = StringUtil::deserialize($res, true);
              if (is_array($arrAnswer['value']))
              {
                  return implode (", ", $arrAnswer['value']);
              }
              else
              {
                  $arrChoices = (strcmp($this->arrData['multiplechoice_subtype'], 'mc_dichotomous') != 0) ? StringUtil::deserialize($this->arrData['choices'], true) : array(0 => $GLOBALS['TL_LANG']['tl_survey_question']['yes'], 1 => $GLOBALS['TL_LANG']['tl_survey_question']['no']);
                  return $arrChoices[$arrAnswer['value']-1];
              }
              if (strlen($arrAnswer['other']))
              {
                  return $arrAnswer['other'];
              }
          }*/

    /**
     * @param array $result
     */
    protected function getQuestionChoices(): array
    {
        if ('mc_dichotomous' === $this->arrData['multiplechoice_subtype']) {
            $choices = [
                1 => [
                    'choice' => $GLOBALS['TL_LANG']['tl_survey_question']['yes'],
                ],
                2 => [
                    'choice' => $GLOBALS['TL_LANG']['tl_survey_question']['no'],
                ],
            ];
        } else {
            $choices = StringUtil::deserialize($this->arrData['choices'], true);
        }

        return $choices;
    }
}
