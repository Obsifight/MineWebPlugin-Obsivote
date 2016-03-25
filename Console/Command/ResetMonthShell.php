<?php
class ResetMonthShell extends AppShell {

	public $uses = array('User', 'Obsivote.Vote'); //Models

    public function main() {

      // Liste des kits
        $kits = Configure::read('Obsivote.kits');

      // On récupère les 15 meilleurs voteurs
        $ranking = $this->User->find('all', array('limit' => count($kits), 'order' => 'vote desc'));

      // On les parcours & on leur ajoute le kit récupèrable
        $i = 1;
        foreach ($ranking as $key => $value) {

          $this->User->read(null, $value['User']['id']);
          $this->User->set(array(
            'obsivote-kit_to_get' => $i
          ));
          $this->User->save();

          $i++;

        }

      // On supprime tous les votes
        $this->Vote->deleteAll(array('1' => '1'));

      // On remet tout le monde à 0
        $this->User->updateAll(array('vote' => 0));


	  	$this->out('Done');
    }
}
