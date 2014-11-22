<?php 
	
	if ( ! defined( 'ABSPATH' ) ) {
		exit;
		// Exit if accessed directly
	}

	
	class WC_Cancel_Dashboard extends WP_List_Table {
		
		function __construct(){
			global $status, $page;
			//Set parent defaults
			parent::__construct( array(            'singular'  => 'order',     //singular name of the listed records
			'plural'    => 'orders',    //plural name of the listed records
			'ajax'      => false        //does this table support ajax?
			) );
		}

		
		function get_data(){
			global $wpdb;
			$requests = $wpdb->get_results("SELECT * FROM ".$wpdb->prefix."wc_cancel_orders WHERE (is_approved=0 || is_approved=1) ORDER BY id DESC",ARRAY_A);
			return $requests;
		}

		
		function get_item_count($id){
			global $wpdb;
			$item_count = $wpdb->get_row("SELECT COUNT(order_id) as item_count FROM ".$wpdb->prefix."woo_booking_dates WHERE order_id =".$id,ARRAY_A);
			return $item_count['item_count'];
		}

		/* function column_default($item, $column_name){
        switch($column_name){
            case 'order_id':
				return $this->column_title($item);
			case 'order_date':
                return $item[$column_name];
			case 'item_count':
				return $this->get_item_count($item['order_id']);
            default:
                return print_r($item,true); //Show the whole array for troubleshooting purposes
        }
    }*/		function column_default($item , $column ) {
			global $woocommerce, $the_order;
			$post = get_post($item['order_id']);
			
			if ($item['order_id']) {
				$the_order = wc_get_order($item['order_id']);
			}

			switch ( $column ) {
				case 'order_status' :
					printf( '<mark class="%s tips" data-tip="%s">%s</mark>', sanitize_title( $the_order->get_status() ), wc_get_order_status_name( $the_order->get_status() ), wc_get_order_status_name( $the_order->get_status() ) );
					break;
				case 'order_date' :
					
					if ( '0000-00-00 00:00:00' == $post->post_date ) {
						$t_time = $h_time = __( 'Unpublished', 'woocommerce' );
					} else {
						$t_time    = get_the_time( __( 'Y/m/d g:i:s A', 'woocommerce' ), $post );
						$gmt_time  = strtotime( $post->post_date_gmt . ' UTC' );
						$time_diff = current_time( 'timestamp', 1 ) - $gmt_time;
						$h_time    = get_the_time( __( 'Y/m/d', 'woocommerce' ), $post );
					}

					echo '<abbr title="' . esc_attr( $t_time ) . '">' . esc_html( apply_filters( 'post_date_column_time', $h_time, $post ) ) . '</abbr>';
					break;
				case 'customer_message' :
					
					if ( $the_order->customer_message )echo '<span class="note-on tips" data-tip="' . esc_attr( $the_order->customer_message ) . '">' . __( 'Yes', 'woocommerce' ) . '</span>'; else echo '<span class="na">&ndash;</span>';
					break;
				case 'order_items' :
					echo '<a href="#" class="show_order_items">' . apply_filters( 'woocommerce_admin_order_item_count', sprintf( _n( '%d item', '%d items', $the_order->get_item_count(), 'woocommerce' ), $the_order->get_item_count() ), $the_order ) . '</a>';
					
					if ( sizeof( $the_order->get_items() ) > 0 ) {
						echo '<table class="order_items" cellspacing="0">';
						foreach ( $the_order->get_items() as $item ) {
							$_product       = apply_filters( 'woocommerce_order_item_product', $the_order->get_product_from_item( $item ), $item );
							$item_meta      = new WC_Order_Item_Meta( $item['item_meta'] );
							$item_meta_html = $item_meta->display( true, true );
							?>
						<tr class="<?php  echo apply_filters( 'woocommerce_admin_order_item_class', '', $item ); ?>">
							<td class="qty"><?php  echo absint( $item['qty'] ); ?></td>
							<td class="name">
								<?php  if ( wc_product_sku_enabled() && $_product && $_product->get_sku() ) echo $_product->get_sku() . ' - '; ?><?php  echo apply_filters( 'woocommerce_order_item_name', $item['name'], $item ); ?>
								<?php  if ( $item_meta_html ) : ?>
									<a class="tips" href="#" data-tip="<?php  echo esc_attr( $item_meta_html ); ?>">[?]</a>
								<?php  endif; ?>
							</td>
						</tr>
						<?php
							}

							echo '</table>';
						} else echo '&ndash;';
						break;
					case 'shipping_address' :
						
						if ( $the_order->get_formatted_shipping_address() )echo '<a target="_blank" href="' . esc_url( 'http://maps.google.com/maps?&q=' . urlencode( $the_order->get_shipping_address() ) . '&z=16' ) . '">'. esc_html( preg_replace( '#<br\s*/?>#i', ', ', $the_order->get_formatted_shipping_address() ) ) .'</a>'; else echo '&ndash;';
						
						if ( $the_order->get_shipping_method() )echo '<small class="meta">' . __( 'Via', 'woocommerce' ) . ' ' . esc_html( $the_order->get_shipping_method() ) . '</small>';
						break;
					case 'order_notes' :
						
						if ( $post->comment_count ) {
							// check the status of the post
							( $post->post_status !== 'trash' ) ? $status = '' :
								$status = 'post-trashed';
								$latest_notes = get_comments( array('post_id'=> $post->ID,'number'=> 1,'status'=> $status) );
								$latest_note = current( $latest_notes );
								
								if ( $post->comment_count == 1 ) {
									echo '<span class="note-on tips" data-tip="' . esc_attr( $latest_note->comment_content ) . '">' . __( 'Yes', 'woocommerce' ) . '</span>';
								} else {
									$note_tip = isset( $latest_note->comment_content ) ? esc_attr( $latest_note->comment_content . '<small style="display:block">' . sprintf( _n( 'plus %d other note', 'plus %d other notes', ( $post->comment_count - 1 ), 'woocommerce' ), ( $post->comment_count - 1 ) ) . '</small>' ) :
										sprintf( _n( '%d note', '%d notes', $post->comment_count, 'woocommerce' ), $post->comment_count );
										echo '<span class="note-on tips" data-tip="' . $note_tip . '">' . __( 'Yes', 'woocommerce' ) . '</span>';
									}

								} else {
									echo '<span class="na">&ndash;</span>';
								}

								break;
							case 'order_total' :
								echo esc_html( strip_tags( $the_order->get_formatted_order_total() ) );
								
								if ( $the_order->payment_method_title ) {
									echo '<small class="meta">' . __( 'Via', 'woocommerce' ) . ' ' . esc_html( $the_order->payment_method_title ) . '</small>';
								}

								break;
							case 'order_title' :
								$customer_tip = '';
								
								if ( $address = $the_order->get_formatted_billing_address() ) {
									$customer_tip .= __( 'Billing:', 'woocommerce' ) . ' ' . $address . '<br/><br/>';
								}

								
								if ( $the_order->billing_phone ) {
									$customer_tip .= __( 'Tel:', 'woocommerce' ) . ' ' . $the_order->billing_phone;
								}

								echo '<div class="tips" data-tip="' . esc_attr( $customer_tip ) . '">';
								
								if ( $the_order->user_id ) {
									$user_info = get_userdata( $the_order->user_id );
								}

								
								if ( ! empty( $user_info ) ) {
									$username = '<a href="user-edit.php?user_id=' . absint( $user_info->ID ) . '">';
									
									if ( $user_info->first_name || $user_info->last_name ) {
										$username .= esc_html( ucfirst( $user_info->first_name ) . ' ' . ucfirst( $user_info->last_name ) );
									} else {
										$username .= esc_html( ucfirst( $user_info->display_name ) );
									}

									$username .= '</a>';
								} else {
									
									if ( $the_order->billing_first_name || $the_order->billing_last_name ) {
										$username = trim( $the_order->billing_first_name . ' ' . $the_order->billing_last_name );
									} else {
										$username = __( 'Guest', 'woocommerce' );
									}

								}

								printf( __( '%s by %s', 'woocommerce' ), '<a href="' . admin_url( 'post.php?post=' . absint( $post->ID ) . '&action=edit' ) . '"><strong>' . esc_attr( $the_order->get_order_number() ) . '</strong></a>', $username );
								
								if ( $the_order->billing_email ) {
									echo '<small class="meta email"><a href="' . esc_url( 'mailto:' . $the_order->billing_email ) . '">' . esc_html( $the_order->billing_email ) . '</a></small>';
								}

								echo '</div>';
								break;
							case 'order_actions' :
								?><p>
					<?php
								do_action( 'woocommerce_admin_order_actions_start', $the_order );
								$actions = array();
								
								if ( $the_order->has_status( array('cancel-request') ) ) {
									$actions['cancelled'] = array('url' => wp_nonce_url( admin_url( 'admin-ajax.php?action=mark_order_cancelled&order_id=' . $post->ID ), 'woocommerce-mark-order-cancel-request' ),'name' => __( 'Approve Request', 'woocommerce' ),'title' => __( 'Approve Request', 'woocommerce' ),'action' => "cancel-request");
									$actions['processing'] = array('url' => wp_nonce_url( admin_url( 'admin-ajax.php?action=woocommerce_mark_order_processing&order_id=' . $post->ID ), 'woocommerce-mark-order-processing' ),'name' => __( 'Deny Request', 'woocommerce' ),'title' => __( 'Deny Request', 'woocommerce' ),'action' => "processing");
								}

								$actions['view'] = array('url' => admin_url( 'post.php?post=' . $post->ID . '&action=edit' ),'name' => __( 'View', 'woocommerce' ),'action' => "view");
								
								if ( $the_order->has_status( array('cancelled') ) ) {
									$actions['viewcancel'] = array('url' => 'javascript:void(0);','name' => __( 'Request Approved', 'woocommerce' ),'action' => "wc-cancelled");
								}

								$actions = apply_filters( 'woocommerce_admin_order_actions', $actions, $the_order );
								foreach ( $actions as $key=> $action ) {
									
									if($key=='cancelled' || $key=='viewcancel'){
										printf( '<a class="button tips %s" href="%s" data-tip="%s">%s</a>', esc_attr( $action['action'] ),esc_url( $action['url'] ), esc_attr( $action['name'] ), esc_attr( $action['name'] ) );
									} else {
										printf( '<a class="button tips %s" href="%s" data-tip="%s">%s</a>', esc_attr( $action['action'] ), esc_url( $action['url'] ), esc_attr( $action['name'] ), esc_attr( $action['name'] ) );
									}

								}

								do_action( 'woocommerce_admin_order_actions_end', $the_order );
								?>
				</p><?php
								break;
					}

				}

				
				function column_cb($item){
					return sprintf('<input type="checkbox" name="%1$s[]" value="%2$s" />',$this->_args['singular'],$item['order_id']);
				}

				
				function get_columns(){
					$columns                     = array();
					$columns['cb']               = '<input type="checkbox" />';
					$columns['order_status']     = '<span class="status_head tips" data-tip="'.esc_attr__( 'Status', 'woocommerce' ).'">'.esc_attr__( 'Status', 'woocommerce' ) . '</span>';
					$columns['order_title']      = __( 'Order', 'woocommerce' );
					$columns['order_items']      = __( 'Purchased', 'woocommerce' );
					$columns['shipping_address'] = __( 'Ship to', 'woocommerce' );
					$columns['customer_message'] = '<span class="notes_head tips" data-tip="' . esc_attr__( 'Customer Message', 'woocommerce' ) . '">' . esc_attr__( 'Customer Message', 'woocommerce' ) . '</span>';
					$columns['order_notes']      = '<span class="order-notes_head tips" data-tip="' . esc_attr__( 'Order Notes', 'woocommerce' ) . '">' . esc_attr__( 'Order Notes', 'woocommerce' ) . '</span>';
					$columns['order_date']       = __( 'Date', 'woocommerce' );
					$columns['order_total']      = __( 'Total', 'woocommerce' );
					$columns['order_actions']    = __( 'Actions', 'woocommerce' );
					return $columns;
				}

				
				function get_sortable_columns() {
					$sortable_columns = array(            'order_total'     => array('order_total',false),     //true means it's already sorted
					'order_date'  => array('order_date',false)        );
					return $sortable_columns;
				}

				
				function get_bulk_actions() {
					$actions = array('delete'=>'Delete','wc_cancell_approve'=>'Approve Requests','wc_cancell_reject'=>'Reject Requests');
					return $actions;
				}

				
				function process_bulk_action() {
					//Detect when a bulk action is being triggered...
					
					if( 'delete'===$this->current_action() && $_POST['wc_cancell']) {
						$this->delete_records();
					}

					
					if( 'wc_cancell_approve'===$this->current_action()  && $_POST['wc_cancell']) {
						$this->approve_records();
					}

					
					if( 'wc_cancell_reject'===$this->current_action()  && $_POST['wc_cancell']) {
						$this->reject_requests_records();
					}

				}

				
				function  delete_records(){
					global $wpdb;
					
					if(isset($_POST['order'])):
					$size=count($_POST['order']);
					for ($i=0; $i < $size; $i++){
						$id = $_POST['order'][$i];
						$wpdb->query("DELETE FROM ".$wpdb->prefix."wc_cancel_orders WHERE order_id =".$id);
					}

					endif;
				}

				
				function  approve_records(){
					global $wpdb;
					
					if(isset($_POST['order'])):
					$size=count($_POST['order']);
					for ($i=0; $i < $size; $i++){
						$id = $_POST['order'][$i];
						$wpdb->update($wpdb->prefix."wc_cancel_orders",array('is_approved'=>1,'cancel_date'=>current_time('mysql')), array('order_id'=>$id), array('%d','%s'), array('%d') );
						$order = wc_get_order( $id );
						$order->update_status( 'cancelled' );
					}

					endif;
				}

				
				function  reject_requests_records(){
					global $wpdb;
					
					if(isset($_POST['order'])):
					$size=count($_POST['order']);
					for ($i=0; $i < $size; $i++){
						$id = $_POST['order'][$i];
						$wpdb->update($wpdb->prefix."wc_cancel_orders",array('is_approved'=>2,'cancel_date'=>current_time('mysql')), array('order_id'=>$id), array('%d','%s'), array('%d') );
						$order = wc_get_order( $id );
						$order->update_status('processing');
					}

					endif;
				}

				
				function prepare_items() {
					global $wpdb;
					//This is used only if making any database queries
					/**
         * First, lets decide how many records per page to show*/        
		 			$per_page = 10;
					$columns = $this->get_columns();
					$hidden = array();
					$sortable = $this->get_sortable_columns();
					$this->_column_headers = array($columns, $hidden, $sortable);
					$this->process_bulk_action();
					$data = $this->get_data();
					
					function usort_reorder($a,$b){
						$orderby = (!empty($_REQUEST['orderby'])) ? $_REQUEST['orderby'] :
						'order_id';
						//If no sort, default to title
						$order = (!empty($_REQUEST['order'])) ? $_REQUEST['order'] :
						'desc';
						//If no order, default to asc
						$result = strcmp($a[$orderby], $b[$orderby]);
						//Determine sort order
						return ($order==='asc') ? $result :
						-$result;
						//Send final sort direction to usort
					}

					usort($data, 'usort_reorder');
					$current_page = $this->get_pagenum();
					$total_items = count($data);
					$data = array_slice($data,(($current_page-1)*$per_page),$per_page);
					$this->items = $data;
					$this->set_pagination_args( array(            'total_items' => $total_items,                  //WE have to calculate the total number of items
					'per_page'    => $per_page,                     //WE have to determine how many items to show on a page
					'total_pages' => ceil($total_items/$per_page)   //WE have to calculate the total number of pages
					) );
				}

			}

			?>