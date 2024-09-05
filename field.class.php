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
 * @package    datafield
 * @subpackage uuid
 * @copyright  2024 onwards Lafayette College ITS
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

use core\notification;

require_once __DIR__ . '/../text/field.class.php';

class data_field_uuid extends data_field_text {
    public $type = 'uuid';

    protected static $priority = self::MAX_PRIORITY;

    function display_add_field($recordid=0, $formdata=null) {
        global $DB, $OUTPUT;

        $readonly = '';
        $context = \context_module::instance($this->cm->id);
        if (!has_capability('datafield/uuid:manage', $context)) {
            $readonly = ' readonly';
        }

        if ($formdata) {
            $fieldname = 'field_' . $this->field->id;
            $content = $formdata->$fieldname;
        } else if ($recordid) {
            $content = $DB->get_field('data_content', 'content', array('fieldid'=>$this->field->id, 'recordid'=>$recordid));
        } else {
            $content = '';
        }

        // beware get_field returns false for new, empty records MDL-18567
        if ($content===false) {
            $content='';
        }

        if (has_capability('datafield/uuid:view', $context)) {
            $str = '<div title="' . s($this->field->description) . '">';
            $str .= '<label for="field_'.$this->field->id.'"><span class="accesshide">'.s($this->field->name).'</span>';
            if ($this->field->required) {
                $image = $OUTPUT->pix_icon('req', get_string('requiredelement', 'form'));
                $str .= html_writer::div($image, 'inline-req');
            }
            $str .= '</label><input class="basefieldinput form-control d-inline mod-data-input" ' .
                    'type="text" name="field_' . $this->field->id . '" ' .
                    'id="field_' . $this->field->id . '" value="' . s($content) . '"' . $readonly . '/>';
            $str .= '</div>';
        } else {
            $str = '<input class="basefieldinput form-control d-inline mod-data-input" ' .
            'type="hidden" name="field_' . $this->field->id . '" ' .
            'id="field_' . $this->field->id . '" value="' . s($content) . '"' . $readonly . '/>';
        }

        return $str;
    }

    /**
     * Update the content of one data field in the data_content table.
     *
     * Capability checks occur here because the field_validation
     * method does not have access to the old record.
     *
     * @param 
     */
    function update_content($recordid, $value, $name='') {
        global $DB;

        // Populate the new record.
        $content = new stdClass();
        $content->fieldid = $this->field->id;
        $content->recordid = $recordid;
        $content->content = clean_param($value, PARAM_NOTAGS);

        $context = \context_module::instance($this->cm->id);

        if ($oldcontent = $DB->get_record('data_content', array('fieldid'=>$this->field->id, 'recordid'=>$recordid))) {
            if(($content->content !== $oldcontent->content) && ! has_capability('datafield/uuid:manage', $context)) {
                notification::error(get_string('readonly', 'datafield_uuid', $this->field->name));
                return false;
            }
            $content->id = $oldcontent->id;
            return $DB->update_record('data_content', $content);
        } else {
            if(!empty($content->content) && ! has_capability('datafield/uuid:manage', $context)) {
                notification::error(get_string('readonly', 'datafield_uuid', $this->field->name));
                return false;
            }
            return $DB->insert_record('data_content', $content);
        }
    }
}