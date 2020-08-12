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
 * This file contains the tracking reporting, based on tool_uploadcourse 2013 Frédéric Massart.
 *
 * @package   tool_uploadactivitycompletions
 * @copyright 2020 Tim St.Clair (https://github.com/frumbert/)
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
defined('MOODLE_INTERNAL') || die();
require_once($CFG->libdir . '/weblib.php');

/**
 * The tracking reporting class.
 *
 * @package   tool_uploadactivitycompletions
 * @copyright 2020 Tim St.Clair (https://github.com/frumbert/)
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class tool_uploadactivitycompletions_tracker {

    /**
     * Constant to output nothing.
     */
    const NO_OUTPUT = 0;

    /**
     * Constant to output HTML.
     */
    const OUTPUT_HTML = 1;

    /**
     * Constant to output plain text.
     */
    const OUTPUT_PLAIN = 2;

    /**
     * @var array columns to display.
     */
    protected $columns = array('line', 'result', 'user', 'id', 'fullname', 'status');

    /**
     * @var int row number.
     */
    protected $rownb = 0;

    /**
     * @var int chosen output mode.
     */
    protected $outputmode;

    /**
     * @var object output buffer.
     */
    protected $buffer;

    /**
     * Constructor.
     *
     * @param int $outputmode desired output mode.
     * @param object $passthrough do we print output as well as buffering it.
     *
     */
    public function __construct($outputmode = self::NO_OUTPUT, $passthrough = null) {
        $this->outputmode = $outputmode;
        if ($this->outputmode == self::OUTPUT_PLAIN) {
            $this->buffer = new progress_trace_buffer(new text_progress_trace(), $passthrough);
        }
        if ($this->outputmode == self::OUTPUT_HTML) {
            $this->buffer = new progress_trace_buffer(new text_progress_trace(), $passthrough);
        }
    }

    /**
     * Finish the output.
     *
     * @return void
     */
    public function finish() {
        if ($this->outputmode == self::NO_OUTPUT) {
            return;
        }

        if ($this->outputmode == self::OUTPUT_HTML) {
            $this->buffer->output(html_writer::end_tag('table'));
        }
    }

    /**
     * Output the results.
     *
     * @param int $total total completions.
     * @param int $added count of completions added.
     * @param int $skipped count of completions skipped.
     * @param int $errors count of errors.
     * @return void
     */
    public function results($total, $added, $skipped, $errors) {
        if ($this->outputmode == self::NO_OUTPUT) {
            return;
        }

        $message = array(
            get_string('completionstotal', 'tool_uploadactivitycompletions', $total),
            get_string('completionsadded', 'tool_uploadactivitycompletions', $added),
            get_string('completionsskipped', 'tool_uploadactivitycompletions', $skipped),
            get_string('completionserrors', 'tool_uploadactivitycompletions', $errors)
        );

        if ($this->outputmode == self::OUTPUT_PLAIN) {
            foreach ($message as $msg) {
                $this->buffer->output($msg);
            }
        }

        if ($this->outputmode == self::OUTPUT_HTML) {
            $this->buffer->output(html_writer::start_tag('ul'));
            foreach ($message as $msg) {
                $this->buffer->output(html_writer::tag('li', htmlspecialchars($msg)));
            }
            $this->buffer->output(html_writer::end_tag('ul'));
        }
    }

    /**
     * Get the outcome indicator
     *
     * @param bool $outcome success or not?
     * @return object
     */
    private function getoutcomeindicator($outcome) {
        global $OUTPUT;

        switch ($this->outputmode) {
            case self::OUTPUT_PLAIN:
                return $outcome ? 'OK' : 'NOK';
            case self::OUTPUT_HTML:
                return $outcome ? $OUTPUT->pix_icon('i/valid', '') : $OUTPUT->pix_icon('i/invalid', '');
            default:
               return;
        }
    }

    /**
     * Output one more line.
     *
     * @param int $line line number.
     * @param bool $outcome success or not?
     * @param array $status array of statuses.
     * @param object $data extra data to display
     * @return void
     */
    public function output($line, $outcome, $status, $data) {
        if ($this->outputmode == self::NO_OUTPUT) {
            return;
        }

        $message = array(
            $line,
            self::getoutcomeindicator($outcome),
            isset($data->user) ? $data->user->username : '',
            isset($data->course) ? $data->course->id : '',
            isset($data->course) ? $data->course->fullname : ''
        );

        if ($this->outputmode == self::OUTPUT_PLAIN) {
            $this->buffer->output(implode("\t", $message));
            if (is_array($status)) {
                $this->buffer->output(implode("\t  ", $status));
            }
        }

        if ($this->outputmode == self::OUTPUT_HTML) {
            $ci = 0;
            $this->rownb++;
            if (is_array($status)) {
                $status = implode(html_writer::empty_tag('br'), $status);
            }
            $this->buffer->output(html_writer::start_tag('tr', array('class' => 'r' . $this->rownb % 2)));
            $this->buffer->output(html_writer::tag('td', $message[0], array('class' => 'c' . $ci++)));
            $this->buffer->output(html_writer::tag('td', $message[1], array('class' => 'c' . $ci++)));
            $this->buffer->output(html_writer::tag('td', $message[2], array('class' => 'c' . $ci++)));
            $this->buffer->output(html_writer::tag('td', $message[3], array('class' => 'c' . $ci++)));
            $this->buffer->output(html_writer::tag('td', $message[4], array('class' => 'c' . $ci++)));
            $this->buffer->output(html_writer::tag('td', $status, array('class' => 'c' . $ci++)));
            $this->buffer->output(html_writer::end_tag('tr'));
        }
    }

    /**
     * Start the output.
     *
     * @return void
     */
    public function start() {
        if ($this->outputmode == self::NO_OUTPUT) {
            return;
        }

        if ($this->outputmode == self::OUTPUT_PLAIN) {
            $columns = array_flip($this->columns);
            unset($columns['status']);
            $columns = array_flip($columns);
            $this->buffer->output(implode("\t", $columns));
        } else if ($this->outputmode == self::OUTPUT_HTML) {
            $ci = 0;
            $this->buffer->output(html_writer::start_tag('table', array('class' => 'generaltable boxaligncenter flexible-wrap',
                'summary' => get_string('uploadactivitycompletionsresult', 'tool_uploadactivitycompletions'))));
            $this->buffer->output(html_writer::start_tag('tr', array('class' => 'heading r' . $this->rownb)));
            $this->buffer->output(html_writer::tag('th', get_string('csvline', 'tool_uploadactivitycompletions'),
                array('class' => 'c' . $ci++, 'scope' => 'col')));
            $this->buffer->output(html_writer::tag('th', get_string('result', 'tool_uploadactivitycompletions'),
                                        array('class' => 'c' . $ci++, 'scope' => 'col')));
            $this->buffer->output(html_writer::tag('th', get_string('username'),
                                        array('class' => 'c' . $ci++, 'scope' => 'col')));
            $this->buffer->output(html_writer::tag('th', get_string('idnumbercourse'),
                                        array('class' => 'c' . $ci++, 'scope' => 'col')));
            $this->buffer->output(html_writer::tag('th', get_string('fullnamecourse'),
                                        array('class' => 'c' . $ci++, 'scope' => 'col')));
            $this->buffer->output(html_writer::tag('th', get_string('status'),
                                        array('class' => 'c' . $ci++, 'scope' => 'col')));
            $this->buffer->output(html_writer::end_tag('tr'));
        }
    }

    /**
     * Return text buffer.
     * @return string buffered plain text
     */
    public function get_buffer() {
        if ($this->outputmode == self::NO_OUTPUT) {
            return "";
        }
        return $this->buffer->get_buffer();
    }

}
