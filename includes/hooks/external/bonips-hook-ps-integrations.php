<?php
if ( ! defined( 'boniPS_VERSION' ) ) exit;

add_filter( 'bonips_setup_hooks', function( $installed ) {
	if ( defined( 'MS_PLUGIN_VERSION' ) ) $installed['hook_ps_memberships'] = array( 'title' => __( 'PS Mitgliedschaften', 'bonips' ), 'description' => __( 'Vergebe %_plural% für Registrierungen und bezahlte Mitgliedschaften.', 'bonips' ), 'callback' => array( 'boniPS_PS_Memberships' ) );
	if ( class_exists( 'CoursePress_Data_Course' ) ) $installed['hook_coursepress'] = array( 'title' => __( 'CoursePress', 'bonips' ), 'description' => __( 'Vergebe %_plural% für Kursfortschritte.', 'bonips' ), 'callback' => array( 'boniPS_CoursePress' ) );
	if ( defined( 'EAB_PLUGIN_DIR' ) ) $installed['hook_events_bookings'] = array( 'title' => __( 'Events and Bookings', 'bonips' ), 'description' => __( 'Vergebe %_plural% für Veranstaltungsbuchungen.', 'bonips' ), 'callback' => array( 'boniPS_Events_Bookings' ) );
	return $installed;
}, 26 );

add_action( 'bonips_load_hooks', function() {
	if ( ! class_exists( 'boniPS_External_Event_Hook' ) ) {
		abstract class boniPS_External_Event_Hook extends boniPS_Hook {
			protected function award( $setting, $reference, $user_id, $entry_id ) {
				$user_id = absint( $user_id );
				if ( ! $user_id || ! $entry_id || $this->core->exclude_user( $user_id ) || $this->over_hook_limit( $setting, $reference, $user_id ) || $this->core->has_entry( $reference, $entry_id, $user_id ) ) return;
				$this->core->add_creds( $reference, $user_id, $this->prefs[ $setting ]['creds'], $this->prefs[ $setting ]['log'], $entry_id, '', $this->bonips_type );
			}
			public function preferences() {
				foreach ( $this->labels as $setting => $label ) : $prefs = $this->prefs;
?>
<div class="hook-instance"><h3><?php echo esc_html( $label ); ?></h3><div class="row"><div class="col-lg-2 col-md-6 col-sm-12 col-xs-12"><div class="form-group"><label><?php echo $this->core->plural(); ?></label><input type="text" name="<?php echo $this->field_name( array( $setting, 'creds' ) ); ?>" value="<?php echo $this->core->number( $prefs[$setting]['creds'] ); ?>" class="form-control" /></div></div><div class="col-lg-4 col-md-6 col-sm-12 col-xs-12"><div class="form-group"><label><?php _e( 'Limit', 'bonips' ); ?></label><?php echo $this->hook_limit_setting( $this->field_name( array( $setting, 'limit' ) ), $this->field_id( array( $setting, 'limit' ) ), $prefs[$setting]['limit'] ); ?></div></div><div class="col-lg-6 col-md-6 col-sm-12 col-xs-12"><div class="form-group"><label><?php _e( 'Protokollvorlage', 'bonips' ); ?></label><input type="text" name="<?php echo $this->field_name( array( $setting, 'log' ) ); ?>" value="<?php echo esc_attr( $prefs[$setting]['log'] ); ?>" class="form-control" /></div></div></div></div>
<?php endforeach;
			}
		}
	}
	if ( defined( 'MS_PLUGIN_VERSION' ) && ! class_exists( 'boniPS_PS_Memberships' ) ) {
		class boniPS_PS_Memberships extends boniPS_External_Event_Hook {
			public $labels = array( 'registration' => 'Registrierung abgeschlossen', 'invoice_paid' => 'Rechnung bezahlt' );
			public function __construct( $prefs, $type ) { parent::__construct( array( 'id' => 'hook_ps_memberships', 'defaults' => array( 'registration' => array( 'creds'=>1, 'log'=>'%plural% für eine abgeschlossene Registrierung', 'limit'=>'1/x' ), 'invoice_paid' => array( 'creds'=>1, 'log'=>'%plural% für eine bezahlte Mitgliedschaftsrechnung', 'limit'=>'0/x' ) ) ), $prefs, $type ); }
			public function run() { if ( $this->prefs['registration']['creds'] != 0 ) add_action( 'ms_controller_frontend_register_user_complete', array( $this, 'registration' ), 10, 1 ); if ( $this->prefs['invoice_paid']['creds'] != 0 ) add_action( 'ms_invoice_paid', array( $this, 'invoice_paid' ), 10, 1 ); }
			public function registration( $user ) { $this->award( 'registration', 'ps_memberships_registration', is_object($user) ? $user->ID : $user, is_object($user) ? $user->ID : $user ); }
			public function invoice_paid( $invoice ) { $this->award( 'invoice_paid', 'ps_memberships_invoice_paid', $invoice->member_id, $invoice->id ); }
		}
	}
	if ( class_exists( 'CoursePress_Data_Course' ) && ! class_exists( 'boniPS_CoursePress' ) ) {
		class boniPS_CoursePress extends boniPS_External_Event_Hook {
			public $labels = array( 'enrolled'=>'Kurs gebucht', 'module_passed'=>'Modul bestanden', 'unit_completed'=>'Lektion abgeschlossen' );
			public function __construct( $prefs, $type ) { parent::__construct( array( 'id'=>'hook_coursepress', 'defaults'=>array( 'enrolled'=>array('creds'=>1,'log'=>'%plural% für eine Kursanmeldung','limit'=>'0/x'), 'module_passed'=>array('creds'=>1,'log'=>'%plural% für ein bestandenes Modul','limit'=>'0/x'), 'unit_completed'=>array('creds'=>1,'log'=>'%plural% für eine abgeschlossene Lektion','limit'=>'0/x') ) ), $prefs, $type ); }
			public function run() { foreach ( array( 'enrolled'=>'coursepress_student_enrolled', 'module_passed'=>'coursepress_student_module_passed', 'unit_completed'=>'coursepress_student_unit_completed' ) as $key=>$action ) if ( $this->prefs[$key]['creds'] != 0 ) add_action( $action, array($this,$key), 10, 5 ); }
			public function enrolled($user,$course) { $this->award('enrolled','coursepress_enrolled',$user,$course); } public function module_passed($user,$module) { $this->award('module_passed','coursepress_module_passed',$user,$module); } public function unit_completed($user,$unit) { $this->award('unit_completed','coursepress_unit_completed',$user,$unit); }
		}
	}
	if ( defined( 'EAB_PLUGIN_DIR' ) && ! class_exists( 'boniPS_Events_Bookings' ) ) {
		class boniPS_Events_Bookings extends boniPS_External_Event_Hook {
			public $labels = array( 'event_published'=>'Veranstaltung veröffentlicht', 'booking_yes'=>'Teilnahme zugesagt', 'booking_paid'=>'Veranstaltung bezahlt' );
			public function __construct( $prefs, $type ) { parent::__construct( array( 'id'=>'hook_events_bookings', 'defaults'=>array( 'event_published'=>array('creds'=>1,'log'=>'%plural% für das Veröffentlichen einer Veranstaltung','limit'=>'0/x'), 'booking_yes'=>array('creds'=>1,'log'=>'%plural% für eine Veranstaltungszusage','limit'=>'0/x'), 'booking_paid'=>array('creds'=>1,'log'=>'%plural% für eine bezahlte Veranstaltungsbuchung','limit'=>'0/x') ) ), $prefs, $type ); }
			public function run() { if($this->prefs['event_published']['creds']!=0)add_action('transition_post_status',array($this,'event_published'),10,3); if($this->prefs['booking_yes']['creds']!=0)add_action('psource_event_booking_yes',array($this,'booking_yes'),10,2); if($this->prefs['booking_paid']['creds']!=0)add_action('eab-ipn-event_paid',array($this,'booking_paid'),10,3); }
			public function event_published($new_status,$old_status,$post) { if($new_status==='publish' && $old_status!=='publish' && $post->post_type==='psource_event')$this->award('event_published','events_event_published',$post->post_author,$post->ID); }
			public function booking_yes($event,$user) { $this->award('booking_yes','events_booking_yes',$user,$event); } public function booking_paid($event,$amount,$booking) { $booking=get_post($booking); if($booking)$this->award('booking_paid','events_booking_paid',$booking->post_author,$booking->ID); }
		}
	}
}, 26 );
