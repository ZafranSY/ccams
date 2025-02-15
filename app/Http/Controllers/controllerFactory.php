<?php

namespace App\Http\Controllers;

class ControllerFactory {
    public function createController($type) {
        switch ($type) {
            case 'Activity':
                return new ActivityController();
            case 'Assessment':
                return new AssessmentController();
            case 'Attendance':
                return new AttendanceController();
            default:
                throw new \InvalidArgumentException("Unknown controller type: " . $type);
        }
    }
}
?>