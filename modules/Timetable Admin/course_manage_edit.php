<?php
/*
Gibbon, Flexible & Open School System
Copyright (C) 2010, Ross Parker

This program is free software: you can redistribute it and/or modify
it under the terms of the GNU General Public License as published by
the Free Software Foundation, either version 3 of the License, or
(at your option) any later version.

This program is distributed in the hope that it will be useful,
but WITHOUT ANY WARRANTY; without even the implied warranty of
MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
GNU General Public License for more details.

You should have received a copy of the GNU General Public License
along with this program. If not, see <http://www.gnu.org/licenses/>.
*/

use Gibbon\Forms\Form;
use Gibbon\Forms\DatabaseFormFactory;
use Gibbon\Tables\DataTable;
use Gibbon\Services\Format;
use Gibbon\Domain\Timetable\CourseGateway;

//Module includes
require_once __DIR__ . '/moduleFunctions.php';

if (isActionAccessible($guid, $connection2, '/modules/Timetable Admin/course_manage_edit.php') == false) {
    //Acess denied
    echo "<div class='error'>";
    echo __($guid, 'You do not have access to this action.');
    echo '</div>';
} else {
    //Proceed!
    echo "<div class='trail'>";
    echo "<div class='trailHead'><a href='".$_SESSION[$guid]['absoluteURL']."'>".__($guid, 'Home')."</a> > <a href='".$_SESSION[$guid]['absoluteURL'].'/index.php?q=/modules/'.getModuleName($_GET['q']).'/'.getModuleEntry($_GET['q'], $connection2, $guid)."'>".__($guid, getModuleName($_GET['q']))."</a> > <a href='".$_SESSION[$guid]['absoluteURL'].'/index.php?q=/modules/'.getModuleName($_GET['q']).'/course_manage.php&gibbonSchoolYearID='.$_GET['gibbonSchoolYearID']."'>".__($guid, 'Manage Courses & Classes')."</a> > </div><div class='trailEnd'>".__($guid, 'Edit Course & Classes').'</div>';
    echo '</div>';

    if (isset($_GET['return'])) {
        returnProcess($guid, $_GET['return'], null, null);
    }

    if (isset($_GET['deleteReturn'])) {
        $deleteReturn = $_GET['deleteReturn'];
    } else {
        $deleteReturn = '';
    }
    $deleteReturnMessage = '';
    $class = 'error';
    if (!($deleteReturn == '')) {
        if ($deleteReturn == 'success0') {
            $deleteReturnMessage = __($guid, 'Your request was completed successfully.');
            $class = 'success';
        }
        echo "<div class='$class'>";
        echo $deleteReturnMessage;
        echo '</div>';
    }

    //Check if school year specified
    $gibbonCourseID = $_GET['gibbonCourseID'];
    if ($gibbonCourseID == '') {
        echo "<div class='error'>";
        echo __($guid, 'You have not specified one or more required parameters.');
        echo '</div>';
    } else {
        try {
            $data = array('gibbonCourseID' => $gibbonCourseID);
            $sql = 'SELECT gibbonCourseID, gibbonDepartmentID, gibbonCourse.name AS name, gibbonCourse.nameShort as nameShort, orderBy, gibbonCourse.description, gibbonCourse.map, gibbonCourse.gibbonSchoolYearID, gibbonSchoolYear.name as yearName, gibbonYearGroupIDList FROM gibbonCourse, gibbonSchoolYear WHERE gibbonCourse.gibbonSchoolYearID=gibbonSchoolYear.gibbonSchoolYearID AND gibbonCourseID=:gibbonCourseID';
            $result = $connection2->prepare($sql);
            $result->execute($data);
        } catch (PDOException $e) {
            echo "<div class='error'>".$e->getMessage().'</div>';
        }

        if ($result->rowCount() != 1) {
            echo "<div class='error'>";
            echo __('The specified record cannot be found.');
            echo '</div>';
        } else {
            //Let's go!
            $values = $result->fetch();

            $form = Form::create('action', $_SESSION[$guid]['absoluteURL'].'/modules/'.$_SESSION[$guid]['module'].'/course_manage_editProcess.php?gibbonCourseID='.$gibbonCourseID);
			$form->setFactory(DatabaseFormFactory::create($pdo));

			$form->addHiddenValue('address', $_SESSION[$guid]['address']);
			$form->addHiddenValue('gibbonSchoolYearID', $values['gibbonSchoolYearID']);

			$row = $form->addRow();
				$row->addLabel('schoolYearName', __('School Year'));
				$row->addTextField('schoolYearName')->isRequired()->readonly()->setValue($values['yearName']);

			$sql = "SELECT gibbonDepartmentID as value, name FROM gibbonDepartment WHERE type='Learning Area' ORDER BY name";
			$row = $form->addRow();
				$row->addLabel('gibbonDepartmentID', __('Learning Area'));
				$row->addSelect('gibbonDepartmentID')->fromQuery($pdo, $sql)->placeholder();

			$row = $form->addRow();
				$row->addLabel('name', __('Name'))->description(__('Must be unique for this school year.'));
				$row->addTextField('name')->isRequired()->maxLength(45);

			$row = $form->addRow();
				$row->addLabel('nameShort', __('Short Name'));
				$row->addTextField('nameShort')->isRequired()->maxLength(6);

			$row = $form->addRow();
				$row->addLabel('orderBy', __('Order'))->description(__('May be used to adjust arrangement of courses in reports.'));
				$row->addNumber('orderBy')->maxLength(6);

			$row = $form->addRow();
				$column = $row->addColumn('blurb');
				$column->addLabel('description', __('Blurb'));
				$column->addEditor('description', $guid)->setRows(20);

			$row = $form->addRow();
				$row->addLabel('map', __('Include In Curriculum Map'));
                $row->addYesNo('map')->isRequired();

			$row = $form->addRow();
				$row->addLabel('gibbonYearGroupIDList', __('Year Groups'))->description(__('Enrolable year groups.'));
				$row->addCheckboxYearGroup('gibbonYearGroupIDList')->loadFromCSV($values);

			$row = $form->addRow();
				$row->addFooter();
                $row->addSubmit();

            $form->loadAllValuesFrom($values);

            echo $form->getOutput();

            echo '<h2>';
            echo __('Edit Classes');
            echo '</h2>';

            $courseGateway = $container->get(CourseGateway::class);

            $classes = $courseGateway->selectClassesByCourseID($gibbonCourseID);

            // DATA TABLE
            $table = DataTable::create('courseClassManage');

            $table->addHeaderAction('add', __('Add'))
                ->setURL('/modules/Timetable Admin/course_manage_class_add.php')
                ->addParam('gibbonSchoolYearID', $values['gibbonSchoolYearID'])
                ->addParam('gibbonCourseID', $gibbonCourseID)
                ->displayLabel();

            $table->addColumn('nameShort', __('Short Name'))->width('20%');
            $table->addColumn('name', __('Name'))->width('20%');
            $table->addColumn('participantsTotal', __('Participants'));
            $table->addColumn('reportable', __('Reportable'))->format(Format::using('yesNo', 'reportable'));

            // ACTIONS
            $table->addActionColumn()
                ->addParam('gibbonSchoolYearID', $values['gibbonSchoolYearID'])
                ->addParam('gibbonCourseID', $gibbonCourseID)
                ->addParam('gibbonCourseClassID')
                ->format(function ($class, $actions) {
                    $actions->addAction('edit', __('Edit'))
                        ->setURL('/modules/Timetable Admin/course_manage_class_edit.php');

                    $actions->addAction('delete', __('Delete'))
                        ->setURL('/modules/Timetable Admin/course_manage_class_delete.php');

                    $actions->addAction('enrolment', __('Enrolment'))
                        ->setIcon('attendance')
                        ->setURL('/modules/Timetable Admin/courseEnrolment_manage_class_edit.php');
                });

            echo $table->render($classes->toDataSet());
        }
    }
}
