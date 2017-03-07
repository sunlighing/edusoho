<?php

namespace Biz\Course\Event;

use Biz\Course\Service\CourseService;
use Biz\Course\Service\MemberService;
use Codeages\Biz\Framework\Event\Event;
use Biz\Course\Service\CourseSetService;
use Codeages\PluginBundle\Event\EventSubscriber;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

/**
 * 实现业务：将courseset下第一个course的第一个teacher作为courseSet的teacher
 * 当course新增、删除，course的teachers变更（增删）时触发
 * Class CourseSetTeacherSubscriber
 * @package Biz\Course\Event
 */
class CourseSetTeacherSubscriber extends EventSubscriber implements EventSubscriberInterface
{
    public static function getSubscribedEvents()
    {
        return array(
            'course.create' => 'onCourseCreate',
            'course.delete' => 'onCourseDelete',
            'course.teachers.update' => 'onCourseTeachersChange'
        );
    }

    public function onCourseCreate(Event $event)
    {
        $course = $event->getSubject();

        $this->calculateCourseTeacher($course);
    }

    public function onCourseDelete(Event $event)
    {
        $course = $event->getSubject();
        $this->calculateCourseTeacher($course);
    }

    public function onCourseTeachersChange(Event $event)
    {
        $course = $event->getSubject();
        $this->calculateCourseTeacher($course);
    }

    private function calculateCourseTeacher($course)
    {
        if(empty($course)){
            return;
        }
        $courseSet = $this->getCourseSetService()->getCourseSet($course['id']);

        $courses = $this->getCourseService()->findCoursesByCourseSetId($courseSet['id']);
        usort($courses, function($c1, $c2){
            if($c1['createdTime'] == $c2['createdTime']){
                return 0;
            }
            return $c1['createdTime'] < $c2['createdTime'] ? -1 : 1;
        });

        $teachers = $this->getMemberService()->findCourseTeachers($courses[0]['id']);
        if(empty($teachers)){
            return;
        }

        usort($teachers, function($t1, $t2){
            if($t1['seq'] == $t2['seq']){
                return $t1['createdTime'] < $t2['createdTime'] ? -1 : 1;
            }
            return $t1['seq'] < $t2['seq'] ? -1 : 1;
        });

        $courseSet['teacherIds'] = array($teachers[0]['userId']);

        $this->getCourseSetService()->updateCourseSet($courseSet['id'], $courseSet);
    }

    /**
     * @return CourseSetService
     */
    protected function getCourseSetService()
    {
        return $this->getBiz()->service('Course:CourseSetService');
    }
    /**
     * @return CourseService
     */
    protected function getCourseService()
    {
        return $this->getBiz()->service('Course:CourseService');
    }

    /**
     * @return MemberService
     */
    protected function getMemberService()
    {
        return $this->getBiz()->service('Course:MemberService');
    }
}