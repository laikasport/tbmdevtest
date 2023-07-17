<?php
if(!defined('ABSPATH')) {die('You are not allowed to call this page directly.');}

class MpdtWebhookJob extends MeprBaseJob {
  //public $webhook, $event, $data;

  public function perform() {
    if( !isset($this->webhook) || empty($this->webhook) ) {
      throw new Exception(__('"webhook" cannot be blank', 'memberpress-developer-tools'));
    }

    if( !isset($this->event) || empty($this->event) ) {
      throw new Exception(__('"event" cannot be blank', 'memberpress-developer-tools'));
    }

    if( !isset($this->data_id) || empty($this->data_id) ) {
      throw new Exception(__('"data_id" cannot be blank', 'memberpress-developer-tools'));
    }

    $whk = MpdtCtrlFactory::fetch('webhooks');
    $data = $whk->get_obj($this->event, $this->data_id);

    if(is_wp_error($data)) {
      throw new Exception($data->get_error_message());
    }

    if( isset($this->event_id) ){
      $evt = new \MeprEvent( $this->event_id );

      // make sure to pass event args
      if( $evt->id ){
        $event_args = (array) $evt->get_args();
        if( !empty($event_args) ){
          $event_args['event_id'] = $evt->id;
          $event_args['event'] = $this->event;
          $data->event_args = $event_args;
        }
      }
    }

    $whk->send($this->webhook, $this->event, $data);
  }
}

