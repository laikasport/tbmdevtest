<?php if(!defined('ABSPATH')) {die('You are not allowed to call this page directly.');}

class MPCA_Checkout_Controller {
  //Note about free transactions: The sequence of creating the free transaction, deleting the subscription
  //if a coupon was used, and calling mepr-signup does not always happen in the same order for all gateways/situations
  //So we need to check in process_signup and have process_sub_destroy_free_txn function in order to make sure
  //that CA records are created properly.
  public function __construct() {
    // Associate the CA with this signup early on in the signup process
    add_action( 'mepr-signup', array( $this, 'process_signup' ) );
    add_action( 'mepr-event-transaction-completed', array( $this, 'process_fallback' ) );

    // In case the user uses a 100% off coupon on a recurring subscription
    add_action( 'mepr-before-subscription-destroy-create-free-transaction', array( $this, 'process_sub_destroy_free_txn' ) );
  }

  public function process_sub_destroy_free_txn($txn) {
    $sub = $txn->subscription();

    $is_corporate_product = get_post_meta($txn->product_id, 'mpca_is_corporate_product', true);

    //The subscription is destroyed so we need to re-associate this CA with the free txn instead
    if($is_corporate_product) {
      $ca = MPCA_Corporate_Account::find_corporate_account_by_obj($sub);
      if($ca) { //avoids fatal error if CA record not found.
        $ca->obj_id = $txn->id;
        $ca->obj_type = 'transactions';
        $ca->store();
      }
    }
  }

  public function process_signup($transaction) {
    // DO NOT create a parent account when a child is signing up from a parent's link
    if(isset($_GET['ca'])) { return; }

    $obj = $transaction;
    $type = 'transactions';

    //For Subscriptions that have a free coupon applied, we need to create the CA under the transaction
    if($transaction->subscription_id > 0 && ($transaction->coupon_id < 1 || ($transaction->coupon_id > 0 && $transaction->amount > 0.00))) {
      $obj = $transaction->subscription();
      $type = 'subscriptions';
    }

    $is_corporate_product = get_post_meta($obj->product_id, 'mpca_is_corporate_product', true);
    $num_sub_accounts = get_post_meta($obj->product_id, 'mpca_num_sub_accounts', true);

    if($is_corporate_product) {

      // check if obj has already been added to corporate_accounts db
      if( is_object( MPCA_Corporate_Account::find_corporate_account_by_obj_id($obj->id, $type) ) ){
        return;
      }

      // create corporate account using the information from above
      $ca = new MPCA_Corporate_Account();
      $ca->obj_id = $obj->id;
      $ca->obj_type = $type;
      $ca->num_sub_accounts = $num_sub_accounts;
      $ca->user_id = $obj->user_id;
      $ca->store();
    }
  }

  /**
   * Create CA account when a group fallback transaction is a corporate account
   *
   * @param MeprEvent $event
   * @return void
   */
  public function process_fallback($event){

    $txn = $event->get_data();

    if(
      ( !isset($txn->txn_type, $txn->product_id, $txn->user_id) ) ||
      MeprTransaction::$fallback_str != $txn->txn_type )
    { return; }

    $is_corporate_product = get_post_meta($txn->product_id, 'mpca_is_corporate_product', true);
    $num_sub_accounts = get_post_meta($txn->product_id, 'mpca_num_sub_accounts', true);

    if( ! $is_corporate_product ){
      return;
    }

    // check if obj has already been added to corporate_accounts db
    if( is_object( MPCA_Corporate_Account::find_corporate_account_by_obj_id($txn->product_id, 'transactions') ) ){
      return;
    }

    // create corporate account using the information from above
    $ca = new MPCA_Corporate_Account();
    $ca->obj_id = $txn->id;
    $ca->obj_type = 'transactions';
    $ca->num_sub_accounts = $num_sub_accounts;
    $ca->user_id = $txn->user_id;
    $ca->store();
  }

}
