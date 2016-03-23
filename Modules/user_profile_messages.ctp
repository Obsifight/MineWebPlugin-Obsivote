<?php
if(isset($VoteResetKits)) {
  echo '<div class="alert alert-info">';
    echo 'Vous avez été le '.$VoteResetKits.' meilleur voteur du mois précédent ! Vous pouvez dès maintenant récupérer votre kit de récompense en cliquant sur ce bouton';
    echo '<a href="'.$this->Html->url(array('controller' => 'voter', 'action' => 'getMonthKit', 'plugin' => 'obsivote')).'" class="btn btn-info pull-right btn-sm" style="margin-top: -8px;">Récupérer</a>';
  echo '</div>';
}
?>
