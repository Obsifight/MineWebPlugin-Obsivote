<?php
class VoterController extends ObsivoteAppController {

  /*public function beforeFilter() {
    parent::beforeFilter();
    $this->Security->unlockedActions = array('config');
  }*/

  public function getPositionFromRPG() {
    $this->autoRender = false;
    $this->response->type('json');

    $this->loadModel('Obsivote.VoteConfiguration');
    $config = $this->VoteConfiguration->find('first');

    $url = $config['VoteConfiguration']['out_url'];

    $user_agent = 'Mozilla/5.0 (Windows NT 6.1; rv:2.0.1) Gecko/20100101 Firefox/4.0.1'; // simule Firefox 4.
      $header[0] = "Accept: text/xml,application/xml,application/xhtml+xml,";
      $header[0] .= "text/html;q=0.9,text/plain;q=0.8,image/png,*/*;q=0.5";
      $header[] = "Cache-Control: max-age=0";
      $header[] = "Connection: keep-alive";
      $header[] = "Keep-Alive: 300";
      $header[] = "Accept-Charset: utf-8";
      $header[] = "Accept-Language: fr"; // langue fr.
      $header[] = "Pragma: "; // Simule un navigateur

    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $url); // l'url visité
    curl_setopt($ch, CURLOPT_FAILONERROR, 1);// Gestion d'erreur
    //curl_setopt($ch, CURLOPT_FOLLOWLOCATION, 1); // autorise la redirection
    curl_setopt($ch, CURLOPT_RETURNTRANSFER,1); // stock la response dans une variable
    curl_setopt($ch, CURLOPT_HTTPHEADER, $header);
    curl_setopt($ch, CURLOPT_PORT, 80); // set port 80
    curl_setopt($ch, CURLOPT_TIMEOUT, 2); //  timeout curl à 15 secondes.

    curl_setopt($ch, CURLOPT_USERAGENT, $user_agent);

    $result=curl_exec($ch);
    $error = curl_errno($ch);
    $code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);

    if(!$result) {
      $this->log('RPG-Paradize check out error ('.$error.') : HTTP CODE : '.$code);
      return $this->response->body(json_encode(array('status' => false)));
    }

    $position = substr($result, strpos($result, 'Position'), 20);
    $position = filter_var($position, FILTER_SANITIZE_NUMBER_INT);

    return $this->response->body(json_encode(array('status' => true, 'position' => intval($position))));
  }

    public function index() {
      $this->loadModel('Obsivote.VoteConfiguration');
      $search = $this->VoteConfiguration->find('first');
      if(!empty($search)) {

        $rewards = $search['VoteConfiguration']['rewards'];
        $rewards = unserialize($rewards);

        $kits = Configure::read('Obsivote.kits');
        $kits_keys = array_keys($kits);
        $ranking = $this->User->find('all', array('limit' => count($kits), 'order' => 'vote desc'));

        $voteURL = $search['VoteConfiguration']['vote_url'];

        if($this->isConnected) {
          $time_vote = $search['VoteConfiguration']['time_vote'];

          $this->loadModel('Obsivote.Vote');
          $get_last_vote = $this->Vote->find('first',
            array('conditions' =>
              array(
                'OR' => array(
                    'user_id' => $this->User->getKey('id'),
                    'ip' => $this->Util->getIP()
                  ),
                )
              )
            );

          if(!empty($get_last_vote['Vote']['created'])) {
              $now = time();
              $last_vote = ($now - strtotime($get_last_vote['Vote']['created']))/60;
          } else {
              $last_vote = null;
          }

          if(!empty($last_vote) && $last_vote < $time_vote) {
            $calcul_wait_time = ($time_vote - $last_vote)*60;
            $calcul_wait_time = $this->Util->secondsToTime($calcul_wait_time); //On le sort en jolie

            $wait_time = array();
            if($calcul_wait_time['d'] > 0) {
              $wait_time[] = $calcul_wait_time['d'].' '.$this->Lang->get('GLOBAL__DATE_R_DAYS');
            }
            if($calcul_wait_time['h'] > 0) {
              $wait_time[] = $calcul_wait_time['h'].' '.$this->Lang->get('GLOBAL__DATE_R_HOURS');
            }
            if($calcul_wait_time['m'] > 0) {
              $wait_time[] = $calcul_wait_time['m'].' '.$this->Lang->get('GLOBAL__DATE_R_MINUTES');
            }
            if($calcul_wait_time['s'] > 0) {
              $wait_time[] = $calcul_wait_time['s'].' '.$this->Lang->get('GLOBAL__DATE_R_SECONDS');
            }

            $wait_time = implode(', ', $wait_time);

            $this->set(compact('wait_time'));
          }
        }

        $this->set(compact('ranking', 'rewards', 'voteURL', 'kits', 'kits_keys', 'isInGame'));
      } else {
          throw new NotFoundException('ObsiVote not configured');
      }
    }

    public function isInGame($user_pseudo) {
      $this->autoRender = false;
      $this->response->type('json');

      $this->loadModel('Obsivote.VoteConfiguration');
      $search = $this->VoteConfiguration->find('first');
      $server_id = $search['VoteConfiguration']['server_id'];

      $isInGame = $this->Server->call(array('isConnected' => $user_pseudo), true, $server_id);
      $isInGame = (isset($isInGame['isConnected']) && $isInGame['isConnected'] == "true") ? true : false;

      echo json_encode(array('isInGame' => $isInGame));
    }

    public function setPseudo() {
      $this->autoRender = false;
      if($this->request->is('ajax')) {
        $this->loadModel('User');
        $user_rank = $this->User->find('first', array('conditions' => array('pseudo' => $this->request->data['pseudo'])));
        if(!empty($user_rank) && $this->Permissions->have($user_rank['User']['rank'], 'VOTE') == "true") {
          if(!empty($this->request->data['pseudo'])) {
            if($this->User->exist($this->request->data['pseudo'])) {

  						$user_id = $this->User->getFromUser('id', $this->request->data['pseudo']);

              $this->loadModel('Obsivote.Vote');
              $get_last_vote = $this->Vote->find('first',
  							array('conditions' =>
  								array(
  									'OR' => array(
  											'user_id' => $user_id,
  											'ip' => $this->Util->getIP()
  										),
  									)
  								)
  							);

              if(!empty($get_last_vote['Vote']['created'])) {
                  $now = time();
                  $last_vote = ($now - strtotime($get_last_vote['Vote']['created']))/60;
              } else {
                  $last_vote = null;
              }

              $this->loadModel('Obsivote.VoteConfiguration');
              $config = $this->VoteConfiguration->find('first');

              $time_vote = $config['VoteConfiguration']['time_vote'];

              if(empty($last_vote) OR $last_vote > $time_vote) {

                  $this->Session->write('vote.pseudo', $this->request->data['pseudo']);
                  echo json_encode(array('statut' => true , 'msg' => $this->Lang->get('VOTE__STEP_1_SUCCESS')));

              } else {

                $calcul_wait_time = ($time_vote - $last_vote)*60;
                $calcul_wait_time = $this->Util->secondsToTime($calcul_wait_time); //On le sort en jolie

                $wait_time = array();
                if($calcul_wait_time['d'] > 0) {
                  $wait_time[] = $calcul_wait_time['d'].' '.$this->Lang->get('GLOBAL__DATE_R_DAYS');
                }
                if($calcul_wait_time['h'] > 0) {
                  $wait_time[] = $calcul_wait_time['h'].' '.$this->Lang->get('GLOBAL__DATE_R_HOURS');
                }
                if($calcul_wait_time['m'] > 0) {
                  $wait_time[] = $calcul_wait_time['m'].' '.$this->Lang->get('GLOBAL__DATE_R_MINUTES');
                }
                if($calcul_wait_time['s'] > 0) {
                  $wait_time[] = $calcul_wait_time['s'].' '.$this->Lang->get('GLOBAL__DATE_R_SECONDS');
                }

                $wait_time = implode(', ', $wait_time);

                echo json_encode(array('statut' => false , 'msg' => $this->Lang->get('VOTE__VOTE_ERROR_WAIT', array('{WAIT_TIME}' => $wait_time))));
              }
            } else {
                echo json_encode(array('statut' => false , 'msg' =>$this->Lang->get('VOTE__VOTE_ERROR_USER_UNKNOWN')));
            }
          }
        } else {
            echo json_encode(array('statut' => false , 'msg' =>$this->Lang->get('VOTE__VOTE_ERROR_USER_UNKNOWN')));
        }
      } else {
          throw new InternalErrorException();
      }
    }

    public function checkOut() {
      $this->autoRender = false;
      if($this->request->is('ajax')) {
        if(!empty($this->request->data['out'])) {
          if($this->Util->isValidReCaptcha($this->request->data['recaptcha'], $this->Util->getIP(), $this->Configuration->getKey('captcha_google_secret'))) {

            $this->loadModel('Obsivote.VoteConfiguration');
            $config = $this->VoteConfiguration->find('first');

            $url = $config['VoteConfiguration']['out_url'];
            // exemple : http://rpg-paradize.com/site-+FR+++RESET++ObsiFight+Serveur+PvP+Faction+2424+1.8-44835

            $user_agent = 'Mozilla/5.0 (Windows NT 6.1; rv:2.0.1) Gecko/20100101 Firefox/4.0.1'; // simule Firefox 4.
              $header[0] = "Accept: text/xml,application/xml,application/xhtml+xml,";
              $header[0] .= "text/html;q=0.9,text/plain;q=0.8,image/png,*/*;q=0.5";
              $header[] = "Cache-Control: max-age=0";
              $header[] = "Connection: keep-alive";
              $header[] = "Keep-Alive: 300";
              $header[] = "Accept-Charset: utf-8";
              $header[] = "Accept-Language: fr"; // langue fr.
              $header[] = "Pragma: "; // Simule un navigateur

            $ch = curl_init();
            curl_setopt($ch, CURLOPT_URL, $url); // l'url visité
            curl_setopt($ch, CURLOPT_FAILONERROR, 1);// Gestion d'erreur
            //curl_setopt($ch, CURLOPT_FOLLOWLOCATION, 1); // autorise la redirection
            curl_setopt($ch, CURLOPT_RETURNTRANSFER,1); // stock la response dans une variable
            curl_setopt($ch, CURLOPT_HTTPHEADER, $header);
            curl_setopt($ch, CURLOPT_PORT, 80); // set port 80
            curl_setopt($ch, CURLOPT_TIMEOUT, 2); //  timeout curl à 15 secondes.

            curl_setopt($ch, CURLOPT_USERAGENT, $user_agent);

            $result=curl_exec($ch);
            $error = curl_errno($ch);
            $code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
            curl_close($ch);

            if($result !== false) {
              $str = substr($result, strpos($result, 'Clic Sortant'), 20);
              $out = filter_var($str, FILTER_SANITIZE_NUMBER_INT);
              $array = array($out, $out-1, $out-2, $out-3, $out+1, $out+2, $out+3);
            } else {
              $this->log('RPG-Paradize check out error ('.$error.') : HTTP CODE : '.$code);
            }

            if($code != 200 || $result === false || in_array($this->request->data['out'], $array)) {

              $this->Session->write('vote.out', true);
              echo json_encode(array('statut' => true, 'msg' =>$this->Lang->get('VOTE__STEP_3_SUCCESS')));

            } else {
              echo json_encode(array('statut' => false, 'msg' =>$this->Lang->get('VOTE__STEP_3_ERROR')));
            }
          } else {
            echo json_encode(array('statut' => false, 'msg' => 'Vous n\'avez pas validé le captcha.'));
          }
        } else {
          echo json_encode(array('statut' => false, 'msg' => 'Vous n\'avez pas entré d\'OUT.'));
        }
      } else {
          throw new InternalErrorException();
      }
    }

    public function getRewards() {
      $this->response->type('json');
      $this->autoRender = false;
      if($this->request->is('ajax')) {
        if($this->Session->check('vote.pseudo') || $this->isConnected) {

          if($this->Session->check('vote.out')) {

            $user_pseudo = ($this->isConnected) ? $this->User->getKey('pseudo') : $this->Session->read('vote.pseudo');

            // check si il a pas déjà voté sur ce site
            $this->loadModel('Obsivote.Vote');
						$user_id = $this->User->getFromUser('id', $user_pseudo);
            $get_last_vote = $this->Vote->find('first', array(
              'conditions' => array(
                'OR' => array(
                  'user_id' => $user_id,
                  'ip' => $this->Util->getIP())
                )
              ));

            if(!empty($get_last_vote['Vote']['created'])) {
                $now = time();
                $last_vote = ($now - strtotime($get_last_vote['Vote']['created']))/60;
            } else {
                $last_vote = null;
            }

            $this->loadModel('Obsivote.VoteConfiguration');
            $config = $this->VoteConfiguration->find('first');

            $time_vote = $config['VoteConfiguration']['time_vote'];

            if(empty($last_vote) OR $last_vote > $time_vote) {

              // on incrémente le vote
              if(empty($get_last_vote)) {
                $this->Vote->read(null, null);
                $this->Vote->set(array(
                    'user_id' => $user_id,
                    'ip' => $this->Util->getIP(),
                ));
                $this->Vote->save();
              } else {
                $this->Vote->read(null, $get_last_vote['Vote']['id']);
                $this->Vote->set(array(
                    'user_id' => $user_id,
                    'ip' => $this->Util->getIP(),
                    'created' => date('Y-m-d H:i:s')
                ));
                $this->Vote->save();
              }

              $server_id = $config['VoteConfiguration']['server_id'];

              $isInGame = $this->Server->call(array('isConnected' => $user_pseudo), true, $server_id);
              $isInGame = (isset($isInGame['isConnected']) && $isInGame['isConnected'] == "true") ? true : false;

              $userData = $this->User->find('first', array('conditions' => array('pseudo' => $user_pseudo)));
              $vote_nbr = $userData['User']['vote'] + 1;
              $this->User->read(null, $userData['User']['id']);

              $data = array('vote' => $vote_nbr);

              if(!$isInGame) {
                $data['rewards_waited'] = (intval($userData['User']['rewards_waited']) + 1);
              }

              /*
                  Gains de PB à chaque vote
              */
                $moneyToAdd = array( //Les probabilités par gain
                  '1' => 10,
                  '2' => 20,
                  '3' => 30,
                  '4' => 15,
                  '5' => 10,
                  '6' => 4,
                  '7' => 3,
                  '8' => 3,
                  '9' => 3,
                  '10' => 2
                );
                $addVoteMoney = $this->Util->random($moneyToAdd, 100);
                $data['money'] = $userData['User']['money'] + intval($addVoteMoney);

              $this->User->set($data);
              $this->User->save();

              // on cast l'event
              $this->getEventManager()->dispatch(new CakeEvent('onVote', $this));


              if($isInGame) { // si c'est maintenant

                $rewardStatus = $this->processRewards($config['VoteConfiguration'], $userData['User'], $addVoteMoney);

								if(!$rewardStatus['status']) {
									echo json_encode(array('statut' => false, 'msg' => $this->Lang->get($rewardStatus['msg'], array('{MONEY_ADDED}' => $addVoteMoney))));
                  return;
								}
								echo json_encode(array('statut' => true, 'msg' => $rewardStatus['msg']));

              } else { // si c'est plus tard
                echo json_encode(array('statut' => true, 'msg' => $this->Lang->get('VOTE__STEP_4_REWARD_SUCCESS_SAVE', array('{MONEY_ADDED}' => $addVoteMoney))));
              }

              $this->Session->delete('vote');

            } else {
              echo json_encode(array('statut' => false, 'msg' => $this->Lang->get('VOTE__VOTE_ERROR_WAIT')));

              $this->Session->delete('vote');
            }

          } else {
              echo json_encode(array('statut' => false, 'msg' => $this->Lang->get('VOTE__STEP_3_ERROR')));
          }
        } else {
            echo json_encode(array('statut' => false, 'msg' => $this->Lang->get('VOTE__VOTE_ERROR_USER_UNKNOWN')));
        }
      } else {
          throw new InternalErrorException();
      }
    }

		private function processRewards($config, $user, $moneyToAdd = false) { // Donne les récompenses au user passé selon la configuration donnée

			/* ====
					Toutes les récompenses
			 	 ==== */
			if($config['rewards_type'] == 1) { // toutes les récompenses

				$rewards = unserialize($config['rewards']); // on récupére la liste

				$this->getEventManager()->dispatch(new CakeEvent('beforeRecieveRewards', $this, $rewards)); // on le passe à l'event

				foreach ($rewards as $key => $value) { // on parcoure les récompenses

					if($value['type'] == 'server') { // si c'est une commande serveur

						$server = $this->executeServerReward($config, $user, $value);
						if($server === true) {

							$rewardsSended[] = $value['name'];

						} else {
							return array('status' => false, 'msg' => $server);
						}

					} elseif($value['type'] == 'money') { // si c'est des points boutique

						$money = intval($user['money']) + intval($value['how']);
						$this->loadModel('User');
						$this->User->setToUser('money', $money, $user['pseudo']);

						$rewardsSended[] = $value['how'].' '.$this->Configuration->getMoneyName();

					} else {
						return array('status' => false, 'msg' => 'VOTE__UNKNOWN_REWARD_TYPE');
					}

				}

				$return = 'Vous avez bien récupéré votre récompense ! ';
        if($moneyToAdd !== false) {
          $return .= 'Vous avez été crédité de '.$moneyToAdd.' points boutique !';
        }
			 	if(!empty($rewardsSended)) {
					$return .= $this->Lang->get('VOTE__REWARDS_TITLE').' : ';
					$return .= '<b>'.implode('</b>, <b>', $rewardsSended).'</b>.';
			 }

			 return array('status' =>true, 'msg' => $return);

		} else { // récompenses aléatoire selon probabilité

			$rewards = unserialize($config['rewards']); // on récupère la liste des récompenses
			$probability_all = 0; // on met la probabilité totale à 0 de base
		 	foreach ($rewards as $key => $value) {
				$probability_all = $probability_all + $value['proba'];   // Puis on la calcule
				$rewards_rand[$key] = $value['proba'];
		 	}

			$reward = $this->Util->random($rewards_rand, $probability_all); // on récupère la reward tiré au sort

			$this->getEventManager()->dispatch(new CakeEvent('beforeRecieveRewards', $this, $reward));

			if($rewards[$reward]['type'] == 'server') { // si c'est une commande serveur

				$server = $this->executeServerReward($config, $user, $rewards[$reward]);
				if($server === true) {

          $return = $this->Lang->get('VOTE__MESSAGE_VOTE_SUCCESS_REWARD').' : <b>'.$rewards[$reward]['name'].'</b>.';
          if($moneyToAdd !== false) {
            $return .= ' Et vous avez été crédité de '.$moneyToAdd.' points boutique !';
          }
					return array('status' => true, 'msg' => $this->Lang->get('VOTE__VOTE_SUCCESS').' ! '.$return);

				} else {
					return array('status' => false, 'msg' => $server);
				}

			 } elseif($rewards[$reward]['type'] == 'money') { // si c'est des points boutique

					 $money = intval($user['money']) + intval($rewards[$reward]['how']);
					 $this->loadModel('User');
					 $this->User->setToUser('money', $money, $user['pseudo']);

					 return array('status' => true, 'msg' =>$this->Lang->get('VOTE__VOTE_SUCCESS').' ! '.$this->Lang->get('VOTE__REWARDS_TITLE').' : <b>'.$rewards[$reward]['how'].' '.$this->Configuration->getMoneyName().'</b>.');

			 } else {
					 return array('status' => false, 'msg' => 'ERROR__INTERNAL_ERROR');
			 }

	 		}

		}

		private function executeServerReward($config, $user, $reward) { // execute la commande d'une récompense si les serveurs de la config sont ouverts

      $server_id = $config['server_id'];
      $server_online = $this->Server->online($server_id);
			if($server_online) { // si tous les serveurs sont allumés

        $cmd = $reward['command'];
        $cmd = str_replace('{PLAYER}', $user['pseudo'], $cmd);
        $cmd = str_replace('{PROBA}', $reward['proba'], $cmd);
        $cmd = str_replace('{REWARD}', $reward['name'], $cmd);

        if($reward['need_connect_on_server'] == "true") {
          $call = $this->Server->call(array('isConnected' => $user['pseudo']), true, $server_id);
          if($call['isConnected'] == 'true') {
            $this->Server->commands($cmd, $server_id);
          } else {
            return 'VOTE__ERROR_NEED_CONNECT_ON_SERVER';
          }
        }

			} else { //le serveur est éteint
				return 'SERVER__MUST_BE_ON';
			}
			return true;
		}

    public function get_reward() {
        $this->autoRender = false;
        if($this->isConnected && $this->User->getKey('rewards_waited') > 0) {
            $this->loadModel('Obsivote.VoteConfiguration');
            $config = $this->VoteConfiguration->find('first');

						$rewardStatus = $this->processRewards($config['VoteConfiguration'], $this->User->getAllFromCurrentUser());

						if(!$rewardStatus['status']) {
							$this->Session->setFlash($this->Lang->get($rewardStatus['msg']), 'default.error');
	            $this->redirect(array('controller' => 'user', 'action' => 'profile', 'plugin' => false));
						}

						$this->User->setKey('rewards_waited', (intval($this->User->getKey('rewards_waited')) - 1));
						$this->Session->setFlash($rewardStatus['msg'], 'default.success');
            $this->redirect(array('controller' => 'user', 'action' => 'profile', 'plugin' => false));

        } else {
            $this->redirect('/');
        }
    }

    public function admin_index() {
      if($this->isConnected AND $this->Permissions->can('MANAGE_VOTE')) {
        $this->layout = "admin";

        $this->loadModel('Obsivote.VoteConfiguration');
        $config = $this->VoteConfiguration->find('first');

        if(!empty($config)) {
            $config = $config['VoteConfiguration'];
            $config['rewards'] = unserialize($config['rewards']);
        } else {
            $config = array();
        }

        $this->set(compact('config'));

        $this->loadModel('Server');

        $servers = $this->Server->findSelectableServers(true);
				$this->set(compact('servers'));

        $this->set('title_for_layout',$this->Lang->get('VOTE__TITLE'));
      } else {
          $this->redirect('/');
      }
    }

    public function config() {
      $this->autoRender = false;
      if($this->isConnected AND $this->Permissions->can('MANAGE_VOTE')) {

        if($this->request->is('post')) {
          if(!empty($this->request->data['server_id']) AND !empty($this->request->data['vote_url']) AND !empty($this->request->data['time_vote']) AND !empty($this->request->data['out_url']) AND $this->request->data['rewards_type'] == '0' OR $this->request->data['rewards_type'] == '1') {
            //if(!empty($this->request->data['rewards'][0]['name']) && $this->request->data['rewards'][0]['name'] != "undefined" && !empty($this->request->data['rewards'][0]['type']) && $this->request->data['rewards'][0]['type'] != "undefined") {

              $this->loadModel('Obsivote.VoteConfiguration');

/*              if($this->request->data['rewards_type'] == 0) {
                foreach ($this->request->data['rewards'] as $key => $value) {
                  if(!isset($value['proba']) || empty($value['proba'])) {
                    echo $this->Lang->get('ERROR__FILL_ALL_FIELDS').'|false';
                    return;
                  }
                }
              }
*/
              $rewards = serialize($this->request->data['rewards']);

              $vote = $this->VoteConfiguration->find('first');
              if(!empty($vote)) {
                  $this->VoteConfiguration->read(null, 1);
              } else {
                  $this->VoteConfiguration->create();
              }
              $this->VoteConfiguration->set(array(
                'time_vote' => $this->request->data['time_vote'],
                'rewards_type' => $this->request->data['rewards_type'],
                'rewards' => $rewards,
                'vote_url' => $this->request->data['vote_url'],
                'out_url' => $this->request->data['out_url'],
                'server_id' => $this->request->data['server_id']
              ));
              $this->VoteConfiguration->save();

              $this->History->set('EDIT_CONFIG', 'vote');

              $this->Session->setFlash($this->Lang->get('VOTE__CONFIGURATION_SUCCESS'), 'default.success');
              echo json_encode(array('statut' => true, 'msg' => $this->Lang->get('VOTE__CONFIGURATION_SUCCESS')));
            /*} else {
                echo json_encode(array('statut' => false, 'msg' => $this->Lang->get('ERROR__FILL_ALL_FIELDS')));
            }*/
          } else {
              echo json_encode(array('statut' => false, 'msg' => $this->Lang->get('ERROR__FILL_ALL_FIELDS')));
          }
        } else {
            echo json_encode(array('statut' => false, 'msg' => $this->Lang->get('ERROR__BAD_REQUEST')));
        }
      } else {
        throw new ForbiddenException();
      }
    }

    function getMonthKit() {
      $this->autoRender = false;
      $kit_to_get = $this->User->getKey('obsivote-kit_to_get');
      if($this->isConnected && !empty($kit_to_get)) {

        $pos = $this->User->getKey('obsivote-kit_to_get');
        $kit_name = array_keys(Configure::read('Obsivote.kits'))[($pos-1)];

        // On vois si le serveur est ouvert
          $this->loadModel('Obsivote.VoteConfiguration');
          $config = $this->VoteConfiguration->find('first');
          $server_id = (isset($config['VoteConfiguration']['server_id'])) ? $config['VoteConfiguration']['server_id'] : false;
          if($this->Server->online($server_id)) {

            // On vois si il est connecté
            $call = $this->Server->call(array('isConnected' => $this->User->getKey('pseudo')), true, $server_id);
            if($call['isConnected'] == 'true') {

              $cmd = 'kit '.$kit_name.' '.$this->User->getKey('pseudo');
              $this->Server->send_command($cmd, $server_id);

              $this->User->setKey('obsivote-kit_to_get', 0);

              $this->Session->setFlash('Vous avez reçu votre kit de récompense !', 'default.success');
              $this->redirect(array('controller' => 'user', 'action' => 'profile', 'plugin' => false));

            } else {
              $this->Session->setFlash('Vous n\'êtes pas connecté sur le serveur, vous ne pouvez pas récupérer votre kit maintenant !', 'default.error');
              $this->redirect(array('controller' => 'user', 'action' => 'profile', 'plugin' => false));
            }

          } else {
            $this->Session->setFlash('Le serveur n\'est pas ouvert, vous ne pouvez pas récupérer votre kit maintenant !', 'default.error');
            $this->redirect(array('controller' => 'user', 'action' => 'profile', 'plugin' => false));
          }

      } else {
        throw new ForbiddenException();
      }
    }

}
