<section class="content">
  <div class="row">
    <div class="col-md-12">
      <div class="box">
        <div class="box-header with-border">
          <h3 class="box-title"><?= $Lang->get('VOTE__TITLE') ?></h3>
        </div>
        <div class="box-body">

          <form action="<?= $this->Html->url(array('action' => 'config', 'plugin' => 'obsivote')) ?>" method="post"  data-ajax="true" data-custom-function="formatteData" data-redirect-url="">

            <div class="ajax-msg"></div>

            <div class="nav-tabs-custom">
            <ul class="nav nav-tabs">
              <li class="active"><a href="#tab_1" data-toggle="tab" aria-expanded="true">Site de vote</a></li>
              <li class=""><a href="#tab_2" data-toggle="tab" aria-expanded="false"><?= $Lang->get('VOTE__CONFIG_REWARDS') ?></a></li>
              <li class="pull-right"><a href="#" class="text-muted"><i class="fa fa-gear"></i></a></li>
            </ul>
            <div class="tab-content">
              <div class="tab-pane active" id="tab_1">

                <div class="form-group">
                  <label><?= $Lang->get('VOTE__CONFIG_TIME_VOTE') ?></label>
                  <input name="time_vote" class="form-control" value="<?= (isset($config['time_vote'])) ? $config['time_vote'] : '' ?>" placeholder="minutes" type="text">
                </div>

                <div class="form-group">
                  <label><?= $Lang->get('VOTE__CONFIG_PAGE_VOTE') ?></label>
                  <input name="vote_url" class="form-control" value="<?= (isset($config['vote_url'])) ? $config['vote_url'] : '' ?>" placeholder="Ex: http://www.rpg-paradize.com/?page=vote&vote=44835" type="text">
                </div>

                <div class="form-group">
                  <label>Page de l'OUT</label>
                  <input name="out_url" class="form-control" value="<?= (isset($config['out_url'])) ? $config['out_url'] : '' ?>" placeholder="Ex: http://rpg-paradize.com/site-+FR+++RESET++ObsiFight+Serveur+PvP+Faction+2424+1.8-44835" type="text">
                </div>

              </div>
              <div class="tab-pane" id="tab_2">

                <div class="form-group">
                  <label><?= $Lang->get('VOTE__CONFIG_REWARDS_TYPE') ?></label>
                  <?php
                    if(@$config['rewards_type'] == 0) {
                      $options = array('0' => $Lang->get('GLOBAL__RANDOM'), '1' => $Lang->get('GLOBAL__ALL'));
                    } else {
                      $options = array('1' => $Lang->get('GLOBAL__ALL'), '0' => $Lang->get('GLOBAL__RANDOM'));
                    }
                  ?>
                  <select class="form-control" name="rewards_type" id="rewards_type">
                    <?php foreach ($options as $key => $value) { ?>
                      <option value="<?= $key ?>"><?= $value ?></option>
                    <?php } ?>
                  </select>
                </div>

                <div class="form-group">
                  <label><?= $Lang->get('SERVER__TITLE') ?></label>
                  <select class="form-control" name="server_id">
                    <?php foreach ($servers as $key => $value) { ?>
                      <option value="<?= $key ?>"<?= (isset($config['server_id']) && $key == $config['server_id']) ? ' selected' : '' ?>><?= $value ?></option>
                    <?php } ?>
                  </select>
                </div>

                <?php if(!empty($config['rewards'])) { ?>
                  <?php $i = 0; foreach ($config['rewards'] as $k => $v) { $i++; ?>
                    <div class="box box-info reward_list" id="reward-<?= $i ?>">
                      <div class="box-body">
                        <div class="form-group">
                          <label><?= $Lang->get('VOTE__CONFIG_REWARD_TYPE') ?></label>
                          <select name="type_reward" class="form-control reward_type">
                            <?php if($v['type'] == "money") { ?>
                              <option value="money"><?= $Lang->get('USER__MONEY') ?></option>
                              <option value="server"><?= $Lang->get('SERVER__TITLE') ?></option>
                            <?php } else { ?>
                              <option value="server"><?= $Lang->get('SERVER__TITLE') ?></option>
                              <option value="money"><?= $Lang->get('USER__MONEY') ?></option>
                            <?php } ?>
                          </select>
                        </div>
                        <div class="form-group">
                          <label><?= $Lang->get('GLOBAL__NAME') ?></label>
                          <input type="text" class="form-control reward_name" name="reward_name" value="<?= $v['name'] ?>">
                        </div>
                        <div class="form-group">
                          <label><?= $Lang->get('VOTE__CONFIG_REWARD_VALUE') ?></label>
                          <?php
                          if($v['type'] == "money") {
                            $reward_value = $v['how'];
                          } else {
                            $reward_value = $v['command'];
                          }
                          ?>
                          <input type="text" name="reward_value" class="form-control reward_value" placeholder="<?= $Lang->get('VOTE__CONFIG_COMMAND_OR_MONEY') ?>" value="<?= $reward_value ?>">
                          <small>
                            <b>{PLAYER}</b> = Pseudo <br>
                            <b>{REWARD}</b> = <?= $Lang->get('VOTE__REWARD_NAME') ?> <br>
                            <b>{PROBA}</b> = <?= $Lang->get('VOTE__CONFIG_REWARD_PROBABILITY') ?> <br>
                            <b>[{+}]</b> <?= $Lang->get('SERVER__PARSE_NEW_COMMAND') ?> <br>
                            <b><?= $Lang->get('GLOBAL__EXAMPLE') ?>:</b> <i>give {PLAYER} 1 1[{+}]broadcast {PLAYER} ...</i></small>
                        </div>
                        <div class="form-group reward_proba_container" style="display:<?= (@$vote['rewards_type'] == 0) ? 'block' : 'none' ?>;">
                          <label><?= $Lang->get('VOTE__CONFIG_REWARD_PROBABILITY') ?></label>
                          <input type="text" name="reward_proba" class="form-control reward_proba" value="<?= $v['proba'] ?>" placeholder="<?= $Lang->get('VOTE__CONFIG_REWARD_PERCENTAGE') ?>">
                        </div>
                        <div class="form-group">
                          <div class="checkbox">
                            <input name="need_connect_on_server" type="checkbox"<?= (isset($v['need_connect_on_server']) && $v['need_connect_on_server'] == "true") ? ' checked=""' : '' ?>>
                            <label><?= $Lang->get('VOTE__CONFIG_REWARD_NEED_CONNECT') ?></label>
                          </div>
                        </div>
                      </div>
                      <div class="box-footer">
                        <button id="<?= $i ?>" class="btn btn-danger pull-right delete"><?= $Lang->get('GLOBAL__DELETE') ?></button><br>
                      </div>
                    </div>
                  <?php } ?>
                <?php } else { $i = 1; ?>
                  <div class="box box-info reward_list" id="reward-1">
                    <div class="box-body">
                      <div class="form-group">
                        <label><?= $Lang->get('VOTE__CONFIG_REWARD_TYPE') ?></label>
                        <select name="type_reward" class="form-control reward_type">
                          <option value="money"><?= $Lang->get('USER__MONEY') ?></option>
                          <option value="server"><?= $Lang->get('SERVER__TITLE') ?></option>
                        </select>
                      </div>
                      <div class="form-group">
                        <label><?= $Lang->get('GLOBAL__NAME') ?></label>
                        <input type="text" class="form-control reward_name" name="reward_name">
                      </div>
                      <div class="form-group">
                        <label><?= $Lang->get('VOTE__CONFIG_REWARD_VALUE') ?></label>
                        <input type="text" name="reward_value" class="form-control reward_value" placeholder="<?= $Lang->get('VOTE__CONFIG_COMMAND_OR_MONEY') ?>">
                      </div>
                      <div class="form-group reward_proba_container" style="display:<?= (@$vote['rewards_type'] == 0) ? 'block' : 'none' ?>;">
                        <label><?= $Lang->get('VOTE__CONFIG_REWARD_PROBABILITY') ?></label>
                        <input type="text" name="reward_proba" class="form-control reward_proba" value="<?= (isset($v['proba'])) ? $v['proba'] : '' ?>" placeholder="<?= $Lang->get('VOTE__CONFIG_REWARD_PERCENTAGE') ?>">
                      </div>
                      <div class="form-group">
                        <div class="checkbox">
                          <input name="need_connect_on_server" type="checkbox">
                          <label><?= $Lang->get('VOTE__CONFIG_REWARD_NEED_CONNECT') ?></label>
                        </div>
                      </div>
                    </div>
                  </div>
                <?php } ?>

                <div id="add-js" data-number="<?= $i ?>"></div>
                <div class="form-group">
                  <a href="#" id="add_reward" class="btn btn-info"><?= $Lang->get('VOTE__CONFIG_ADD_REWARD') ?></a>
                </div>

              </div>

            </div>

            <div class="pull-right">
              <button class="btn btn-primary" type="submit"><?= $Lang->get('GLOBAL__SUBMIT') ?></button>
            </div>
          </form>

        </div>
      </div>
    </div>
  </div>
</section>
<script>

  $('#rewards_type').change(function(e) {
    if($(this).val() == "0") {
      $.each($('.reward_proba_container'), function(index, value) {
        $(value).show();
      });
    } else {
      $.each($('.reward_proba_container'), function(index, value) {
        $(value).hide();
      });
    }
  });

  $('.delete').click(function(e) {
    e.preventDefault();
    var id = $(this).attr('id');
    $('#reward-'+id).slideUp(500).empty();
  });

  $('#add_reward').click(function(e) {
    e.preventDefault();
    var how = $('#add-js').attr('data-number');
    how = parseInt(how) + 1;

    if($('#rewards_type').val() == "0") {
      var display_proba = 'block';
    } else {
      var display_proba = 'none';
    }

    var add = '';
    add +='<div class="box box-info reward_list" id="reward-'+how+'">';
      add +='<div class="box-body">';
        add +='<div class="form-group">';
          add +='<label><?= $Lang->get('VOTE__CONFIG_REWARD_TYPE') ?></label>';
            add +='<select name="type_reward" class="form-control reward_type">';
              add +='<option value="money"><?= $Lang->get('USER__MONEY') ?></option>';
              add +='<option value="server"><?= $Lang->get('SERVER__TITLE') ?></option>';
            add +='</select>';
          add +='</div>';
        add +='<div class="form-group">';
          add +='<label><?= $Lang->get('GLOBAL__NAME') ?></label>';
          add +='<input type="text" class="form-control reward_name" name="reward_name">';
        add +='</div>';
        add +='<div class="form-group">';
          add +='<label><?= $Lang->get('VOTE__CONFIG_REWARD_VALUE') ?></label>';
          add +='<input type="text" name="reward_value" class="form-control reward_value" placeholder="<?= $Lang->get('VOTE__CONFIG_COMMAND_OR_MONEY') ?>">';
        add +='</div>';
        add +='<div class="form-group reward_proba_container" style="display:'+display_proba+';">';
          add +='<label><?= $Lang->get('VOTE__CONFIG_REWARD_PROBABILITY') ?></label>';
          add +='<input type="text" name="reward_proba" class="form-control reward_proba" placeholder="<?= addslashes($Lang->get('VOTE__CONFIG_REWARD_PERCENTAGE')) ?>">';
        add +='</div>';
        add += '<div class="form-group">';
          add += '<div class="checkbox">';
            add += '<input name="need_connect_on_server" type="checkbox">';
            add += '<label><?= $Lang->get('VOTE__CONFIG_REWARD_NEED_CONNECT') ?></label>';
          add += '</div>';
        add += '</div>';
      add +='</div>';
    add +='</div>';
    $('#add-js').append(add);
    $('#add-js').attr('data-number', how);
  });
</script>
<script>
  function formatteData($form) {

    var server_id = $form.find("select[name='server_id']").val();
    var rewards_type = $form.find("select[name='rewards_type']").val();

    var vote_url = $form.find("input[name='vote_url']").val();
    var out_url = $form.find("input[name='out_url']").val();
    var time_vote = $form.find("input[name='time_vote']").val();

    // récompenses
    var rewards = {};
    var i = 0;
    $.each($('.reward_list'), function(index, value) {
      var reward_infos = $(value);

      if(reward_infos.find('select[name="type_reward"]').val() == "server") {
        rewards[i] = {
          type : reward_infos.find('select[name="type_reward"]').val(),
          name : reward_infos.find('input[name="reward_name"]').val(),
          command : reward_infos.find('input[name="reward_value"]').val(),
          proba : reward_infos.find('input[name="reward_proba"]').val(),
          need_connect_on_server : reward_infos.find('input[name="need_connect_on_server"]').is(':checked')
        }
      } else {
        rewards[i] = {
          type : reward_infos.find('select[name="type_reward"]').val(),
          name : reward_infos.find('input[name="reward_name"]').val(),
          how : reward_infos.find('input[name="reward_value"]').val(),
          proba : reward_infos.find('input[name="reward_proba"]').val(),
          need_connect_on_server : reward_infos.find('input[name="need_connect_on_server"]').is(':checked')
        }
      }
      i++;
    });
    //

   return {server_id : server_id, rewards_type : rewards_type, rewards : rewards, vote_url : vote_url, out_url : out_url, time_vote : time_vote};
  }
</script>
