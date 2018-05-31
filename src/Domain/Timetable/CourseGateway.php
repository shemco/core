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

namespace Gibbon\Domain\Timetable;

use Gibbon\Domain\Traits\TableAware;
use Gibbon\Domain\QueryCriteria;
use Gibbon\Domain\QueryableGateway;

/**
 * @version v16
 * @since   v16
 */
class CourseGateway extends QueryableGateway
{
    use TableAware;

    private static $tableName = 'gibbonCourse';

    private static $searchableColumns = ['gibbonCourse.name', 'gibbonCourse.nameShort'];
    
    /**
     * @param QueryCriteria $criteria
     * @return DataSet
     */
    public function queryCoursesBySchoolYear(QueryCriteria $criteria, $gibbonSchoolYearID)
    {
        $query = $this
            ->newQuery()
            ->from($this->getTableName())
            ->cols([
                'gibbonCourse.gibbonCourseID', 'gibbonCourse.name', 'gibbonCourse.nameShort', 'gibbonDepartment.name as department', 'COUNT(DISTINCT gibbonCourseClassID) as classCount'
            ])
            ->leftJoin('gibbonDepartment', 'gibbonDepartment.gibbonDepartmentID=gibbonCourse.gibbonDepartmentID')
            ->leftJoin('gibbonCourseClass', 'gibbonCourseClass.gibbonCourseID=gibbonCourse.gibbonCourseID')
            ->where('gibbonSchoolYearID = :gibbonSchoolYearID')
            ->bindValue('gibbonSchoolYearID', $gibbonSchoolYearID)
            ->groupBy(['gibbonCourse.gibbonCourseID']);

        $criteria->addFilterRules([
            'yearGroup' => function ($query, $gibbonYearGroupID) {
                return $query
                    ->where('FIND_IN_SET(:gibbonYearGroupID, gibbonCourse.gibbonYearGroupIDList)')
                    ->bindValue('gibbonYearGroupID', $gibbonYearGroupID);
            },
        ]);

        return $this->runQuery($query, $criteria);
    }

    public function selectClassesByCourseID($gibbonCourseID)
    {
        $data = array('gibbonCourseID' => $gibbonCourseID);
        $sql = "SELECT gibbonCourseClass.*, COUNT(CASE WHEN gibbonPerson.status='Full' THEN gibbonPerson.status END) as participantsActive, COUNT(CASE WHEN gibbonPerson.status='Expected' THEN gibbonPerson.status END) as participantsExpected, COUNT(DISTINCT gibbonPerson.gibbonPersonID) as participantsTotal 
            FROM gibbonCourseClass 
            LEFT JOIN gibbonCourseClassPerson ON (gibbonCourseClassPerson.gibbonCourseClassID=gibbonCourseClass.gibbonCourseClassID AND NOT gibbonCourseClassPerson.role LIKE '% - Left') 
            LEFT JOIN gibbonPerson ON (gibbonCourseClassPerson.gibbonPersonID=gibbonPerson.gibbonPersonID AND (gibbonPerson.status='Full' OR gibbonPerson.status='Expected')) 
            WHERE gibbonCourseClass.gibbonCourseID=:gibbonCourseID
            GROUP BY gibbonCourseClass.gibbonCourseClassID
            ORDER BY gibbonCourseClass.nameShort";

        return $this->db()->select($sql, $data);
    }

    public function selectCourseEnrolmentByRollGroup($gibbonRollGroupID)
    {
        $data = array('gibbonRollGroupID' => $gibbonRollGroupID);
        $sql = "SELECT DISTINCT gibbonPerson.gibbonPersonID, gibbonPerson.surname, gibbonPerson.preferredName, gibbonRollGroup.name as rollGroup, (SELECT COUNT(*) FROM gibbonCourseClassPerson WHERE gibbonCourseClassPerson.gibbonPersonID=gibbonStudentEnrolment.gibbonPersonID) as classCount
                FROM gibbonPerson 
                JOIN gibbonStudentEnrolment ON (gibbonPerson.gibbonPersonID=gibbonStudentEnrolment.gibbonPersonID) 
                JOIN gibbonRollGroup ON (gibbonStudentEnrolment.gibbonRollGroupID=gibbonRollGroup.gibbonRollGroupID) 
                WHERE gibbonRollGroup.gibbonRollGroupID=:gibbonRollGroupID 
                AND gibbonPerson.status='Full' 
                ORDER BY gibbonPerson.surname, gibbonPerson.preferredName";

        return $this->db()->select($sql, $data);
    }
}