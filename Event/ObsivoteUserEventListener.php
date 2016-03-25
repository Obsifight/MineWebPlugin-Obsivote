<?php
App::uses('CakeEventListener', 'Event');

class ObsivoteUserEventListener implements CakeEventListener {

  private $controller;

 public function __construct($request, $response, $controller) {
   $this->controller = $controller;
 }

  public function implementedEvents() {
      return array(
          'onLoadPage' => 'setProfileVars'
      );
  }

  public function setProfileVars($event) {

    if($this->controller->params['controller'] == "user" && $this->controller->params['action'] == "profile") {

      $user = $this->controller->User->getAllFromCurrentUser();
      if(!empty($user)) {
        $user['isAdmin'] = $this->controller->User->isAdmin();
      }

      /*
        Notifications de kits en attente (meilleur voteur)
      */
      if(!empty($user['obsivote-kit_to_get'])) {
        if($user['obsivote-kit_to_get'] == 1) {
          $place = '1er';
        } else {
          $place = $user['obsivote-kit_to_get'].'ème';
        }

        ModuleComponent::$vars['VoteResetKits'] = $place;
      }

    }

  }

}
