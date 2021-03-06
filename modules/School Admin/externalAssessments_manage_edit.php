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
use Gibbon\Tables\DataTable;
use Gibbon\Services\Format;
use Gibbon\Domain\School\ExternalAssessmentGateway;

//Module includes
require_once __DIR__ . '/moduleFunctions.php';

if (isActionAccessible($guid, $connection2, '/modules/School Admin/externalAssessments_manage_edit.php') == false) {
    //Acess denied
    echo "<div class='error'>";
    echo __($guid, 'You do not have access to this action.');
    echo '</div>';
} else {
    //Proceed!
    echo "<div class='trail'>";
    echo "<div class='trailHead'><a href='".$_SESSION[$guid]['absoluteURL']."'>".__($guid, 'Home')."</a> > <a href='".$_SESSION[$guid]['absoluteURL'].'/index.php?q=/modules/'.getModuleName($_GET['q']).'/'.getModuleEntry($_GET['q'], $connection2, $guid)."'>".__($guid, getModuleName($_GET['q']))."</a> > <a href='".$_SESSION[$guid]['absoluteURL'].'/index.php?q=/modules/'.getModuleName($_GET['q'])."/externalAssessments_manage.php'>".__($guid, 'Manage External Assessments')."</a> > </div><div class='trailEnd'>".__($guid, 'Edit External Assessment').'</div>';
    echo '</div>';

    if (isset($_GET['return'])) {
        returnProcess($guid, $_GET['return'], null, null);
    }

    //Check if school year specified
    $gibbonExternalAssessmentID = $_GET['gibbonExternalAssessmentID'];
    if ($gibbonExternalAssessmentID == '') {
        echo "<div class='error'>";
        echo __($guid, 'You have not specified one or more required parameters.');
        echo '</div>';
    } else {
        try {
            $data = array('gibbonExternalAssessmentID' => $gibbonExternalAssessmentID);
            $sql = 'SELECT * FROM gibbonExternalAssessment WHERE gibbonExternalAssessmentID=:gibbonExternalAssessmentID';
            $result = $connection2->prepare($sql);
            $result->execute($data);
        } catch (PDOException $e) {
            echo "<div class='error'>".$e->getMessage().'</div>';
        }

        if ($result->rowCount() != 1) {
            echo "<div class='error'>";
            echo __($guid, 'The specified record cannot be found.');
            echo '</div>';
        } else {
            //Let's go!
            $values = $result->fetch();

            $form = Form::create('externalAssessmentEdit', $_SESSION[$guid]['absoluteURL'].'/modules/'.$_SESSION[$guid]['module'].'/externalAssessments_manage_editProcess.php?gibbonExternalAssessmentID='.$gibbonExternalAssessmentID);

            $form->addHiddenValue('address', $_SESSION[$guid]['address']);
            $form->addHiddenValue('gibbonExternalAssessmentID', $gibbonExternalAssessmentID);

            $row = $form->addRow();
                $row->addLabel('name', __('Name'))->description(__('Must be unique.'));
                $row->addTextField('name')->isRequired()->maxLength(50);

            $row = $form->addRow();
                $row->addLabel('nameShort', __('Short Name'))->description(__('Must be unique.'));
                $row->addTextField('nameShort')->isRequired()->maxLength(10);

            $row = $form->addRow();
                $row->addLabel('description', __('Description'))->description(__('Brief description of assessment and how it is used.'));
                $row->addTextField('description')->isRequired()->maxLength(255);

            $row = $form->addRow();
                $row->addLabel('active', __('Active'));
                $row->addYesNo('active')->isRequired();

            $row = $form->addRow();
                $row->addLabel('allowFileUpload', __('Allow File Upload'))->description(__('Should the student record include the option of a file upload?'));
                $row->addYesNo('allowFileUpload')->isRequired()->selected('N');

            $row = $form->addRow();
                $row->addFooter();
                $row->addSubmit();

            $form->loadAllValuesFrom($values);

            echo $form->getOutput();

            echo '<h2>';
            echo __('Edit Fields');
            echo '</h2>';

            $externalAssessmentGateway = $container->get(ExternalAssessmentGateway::class);

            // QUERY
            $criteria = $externalAssessmentGateway->newQueryCriteria()
                ->sortBy(['category', 'order'])
                ->fromPOST();

            $externalAssessments = $externalAssessmentGateway->queryExternalAssessmentFields($criteria, $gibbonExternalAssessmentID);

            // DATA TABLE
            $table = DataTable::createPaginated('externalAssessmentManage', $criteria);

            $table->addHeaderAction('add', __('Add'))
                ->setURL('/modules/School Admin/externalAssessments_manage_edit_field_add.php')
                ->addParam('gibbonExternalAssessmentID', $gibbonExternalAssessmentID)
                ->displayLabel();

            $table->addColumn('name', __('Name'));
            $table->addColumn('category', __('Category'));
            $table->addColumn('order', __('Order'));
                
            // ACTIONS
            $table->addActionColumn()
                ->addParam('gibbonExternalAssessmentID', $gibbonExternalAssessmentID)
                ->addParam('gibbonExternalAssessmentFieldID')
                ->format(function ($externalAssessment, $actions) {
                    $actions->addAction('edit', __('Edit'))
                            ->setURL('/modules/School Admin/externalAssessments_manage_edit_field_edit.php');

                    $actions->addAction('delete', __('Delete'))
                            ->setURL('/modules/School Admin/externalAssessments_manage_edit_field_delete.php');
                });

            echo $table->render($externalAssessments);
        }
    }
}
