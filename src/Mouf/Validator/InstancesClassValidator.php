<?php
namespace Mouf\Validator;

use Mouf\MoufManager;
use Mouf\MoufInstanceDescriptor;

/**
 * Validates that all instances are assigned to a class that does exist, that the compulory constructor params are set
 * and that the callback code is valid.
 */
class InstancesClassValidator implements MoufStaticValidatorInterface {

    /**
     * Runs the validation of the class.
     * Returns a MoufValidatorResult explaining the result.
     *
     * @return MoufValidatorResult
     */
    public static function validateClass() {
        $moufManager = MoufManager::getMoufManager();

        $instancesList = $moufManager->getInstancesList();
        $selfedit = isset($_GET['selfedit'])?$_GET['selfedit']:"";

        $errors = array();
        $instancesToDelete = array();
        foreach ($instancesList as $instanceName=>$className) {
            $instanceDescriptor = $moufManager->getInstanceDescriptor($instanceName);
            if ($instanceDescriptor->getType() == MoufInstanceDescriptor::TYPE_DECLARATIVE && !class_exists($className)) {
                $errors[] = "<li>".$instanceName." - Unable to find class: <strong>".$className."</strong> : <a href='".MOUF_URL."mouf/deleteInstance?instanceName=".urlencode($instanceName)."&selfedit=".$selfedit."&returnurl=".urlencode(MOUF_URL."validate/?selfedit=".$selfedit)."' class='btn btn-danger'><i class='icon-remove icon-white'></i> Delete</a></li>";
                $instancesToDelete[] = $instanceName;
            } elseif ($instanceDescriptor->getType() == MoufInstanceDescriptor::TYPE_PHP && $className != null && !class_exists($className)) {
                $errors[] = "<li>".$instanceName." - Unable to find class '<strong>".$className."</strong>' in instance defined by PHP code : <a href='".MOUF_URL."mouf/deleteInstance?instanceName=".urlencode($instanceName)."&selfedit=".$selfedit."&returnurl=".urlencode(MOUF_URL."validate/?selfedit=".$selfedit)."' class='btn btn-danger'><i class='icon-remove icon-white'></i> Delete</a></li>";
                $instancesToDelete[] = $instanceName;
            } else {
                // Let's check the constructor arguments.
                $additionalErrors = $instanceDescriptor->validate();
                $errors = array_merge($errors, array_map(function($text) use ($instanceName) {
                    return '<li>'.$text.'</li>';
                }, $additionalErrors));
            }
        }

        if ($errors) {
            $msg = "";
            if(!empty($instancesToDelete)) {
                $deleteAllAction = MOUF_URL."mouf/deleteAllInstances";
                $msg .= '<form action="'.$deleteAllAction.'" method="POST">';
                $msg .= '<input type="hidden" name="selfedit" value="'.$instanceName.'" />';
                $msg .= '<input type="hidden" name="returnurl" value="'.MOUF_URL."validate/?selfedit=".$selfedit.'" />';
                foreach($instancesToDelete as $instanceName) {
                    $msg .= '<input type="hidden" name="instancesNames[]" value="'.$instanceName.'" />';
                }
                $msg .= 'Want to delete all instances in a row ? Click <button type="submit" class="btn btn-danger">here</button> !<br />';
                $msg .= '</form>';
            }
            $msg .= "The following instances are erroneous:<br/><ul>";
            $msg .= implode("\n", $errors);
            $msg .= "</ul>";
            return new MoufValidatorResult(MoufValidatorResult::ERROR, $msg);
        } else {
            return new MoufValidatorResult(MoufValidatorResult::SUCCESS, "All your instances are associated with existing classes.");
        }

    }

}