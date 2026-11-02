<?php
if ( ! defined( 'boniPS_VERSION' ) ) exit;

add_filter( 'bonips_setup_hooks', 'bonips_register_ps_community_hook', 25 );
function bonips_register_ps_community_hook( $installed ) {

	if ( ! defined( 'CPC2_TEXT_DOMAIN' ) ) return $installed;

	$installed['hook_ps_community'] = array(
		'title'         => __( 'PS Community', 'bonips' ),
		'description'   => __( 'Vergebe %_plural% für Aktivitäten, Foren, Gruppen und Freundschaften in PS Community.', 'bonips' ),
		'callback'      => array( 'boniPS_PS_Community' )
	);

	return $installed;

}

add_action( 'bonips_load_hooks', 'bonips_load_ps_community_hook', 25 );
function bonips_load_ps_community_hook() {

	if ( class_exists( 'boniPS_PS_Community' ) || ! defined( 'CPC2_TEXT_DOMAIN' ) ) return;

	class boniPS_PS_Community extends boniPS_Hook {

		public function __construct( $hook_prefs, $type = BONIPS_DEFAULT_TYPE_KEY ) {

			parent::__construct( array(
				'id'       => 'hook_ps_community',
				'defaults' => array(
					'activity_post' => array(
						'creds' => 1,
						'log'   => '%plural% für einen neuen Community-Beitrag',
						'limit' => '1/d'
					),
					'activity_deleted' => array(
						'creds' => -1,
						'log'   => '%singular% Abzug für das Löschen eines Community-Beitrags',
						'limit' => '0/x'
					),
					'activity_comment' => array(
						'creds' => 1,
						'log'   => '%plural% für einen neuen Aktivitätskommentar',
						'limit' => '1/d'
					),
					'activity_comment_deleted' => array(
						'creds' => -1,
						'log'   => '%singular% Abzug für das Löschen eines Aktivitätskommentars',
						'limit' => '0/x'
					),
					'forum_post' => array(
						'creds' => 1,
						'log'   => '%plural% für ein neues Forenthema',
						'limit' => '1/d'
					),
					'forum_post_deleted' => array(
						'creds' => -1,
						'log'   => '%singular% Abzug für das Löschen eines Forenthemas',
						'limit' => '0/x'
					),
					'forum_reply' => array(
						'creds' => 1,
						'log'   => '%plural% für eine neue Forenantwort',
						'limit' => '1/d'
					),
					'forum_reply_deleted' => array(
						'creds' => -1,
						'log'   => '%singular% Abzug für das Löschen einer Forenantwort',
						'limit' => '0/x'
					),
					'group_created' => array(
						'creds' => 1,
						'log'   => '%plural% für das Erstellen einer Gruppe',
						'limit' => '1/d'
					),
					'group_deleted' => array(
						'creds' => -1,
						'log'   => '%singular% Abzug für das Löschen einer Gruppe',
						'limit' => '0/x'
					),
					'group_joined' => array(
						'creds' => 1,
						'log'   => '%plural% für den Beitritt zu einer Gruppe',
						'limit' => '1/d'
					),
					'group_left' => array(
						'creds' => -1,
						'log'   => '%singular% Abzug für das Verlassen einer Gruppe',
						'limit' => '0/x'
					),
					'friendship_accepted' => array(
						'creds' => 1,
						'log'   => '%plural% für das Annehmen einer Freundschaftsanfrage',
						'limit' => '1/d'
					),
					'friendship_removed' => array(
						'creds' => -1,
						'log'   => '%singular% Abzug für das Beenden einer Freundschaft',
						'limit' => '0/x'
					)
				)
			), $hook_prefs, $type );

			$this->migrate_default_log_templates();

		}

		private function migrate_default_log_templates() {

			$templates = array(
				'activity_post' => array( '%plural% for a new community activity', '%plural% für einen neuen Community-Beitrag' ),
				'activity_deleted' => array( '%singular% deduction for deleting a community activity', '%singular% Abzug für das Löschen eines Community-Beitrags' ),
				'activity_comment' => array( '%plural% for a new activity comment', '%plural% für einen neuen Aktivitätskommentar' ),
				'activity_comment_deleted' => array( '%singular% deduction for deleting an activity comment', '%singular% Abzug für das Löschen eines Aktivitätskommentars' ),
				'forum_post' => array( '%plural% for a new forum topic', '%plural% für ein neues Forenthema' ),
				'forum_post_deleted' => array( '%singular% deduction for deleting a forum topic', '%singular% Abzug für das Löschen eines Forenthemas' ),
				'forum_reply' => array( '%plural% for a new forum reply', '%plural% für eine neue Forenantwort' ),
				'forum_reply_deleted' => array( '%singular% deduction for deleting a forum reply', '%singular% Abzug für das Löschen einer Forenantwort' ),
				'group_created' => array( '%plural% for creating a group', '%plural% für das Erstellen einer Gruppe' ),
				'group_deleted' => array( '%singular% deduction for deleting a group', '%singular% Abzug für das Löschen einer Gruppe' ),
				'group_joined' => array( '%plural% for joining a group', '%plural% für den Beitritt zu einer Gruppe' ),
				'group_left' => array( '%singular% deduction for leaving a group', '%singular% Abzug für das Verlassen einer Gruppe' ),
				'friendship_accepted' => array( '%plural% for accepting a friendship request', '%plural% für das Annehmen einer Freundschaftsanfrage' ),
				'friendship_removed' => array( '%singular% deduction for ending a friendship', '%singular% Abzug für das Beenden einer Freundschaft' )
			);

			foreach ( $templates as $setting => $template ) {
				if ( isset( $this->prefs[ $setting ]['log'] ) && $this->prefs[ $setting ]['log'] === $template[0] )
					$this->prefs[ $setting ]['log'] = $template[1];
			}

		}

		public function run() {

			if ( $this->prefs['activity_post']['creds'] != 0 )
				add_action( 'cpc_activity_post_add_hook', array( $this, 'activity_post' ), 10, 3 );

			if ( $this->prefs['activity_comment']['creds'] != 0 )
				add_action( 'cpc_activity_comment_add_hook', array( $this, 'activity_comment' ), 10, 2 );

			if ( $this->prefs['forum_post']['creds'] != 0 )
				add_action( 'cpc_forum_post_add_hook', array( $this, 'forum_post' ), 10, 3 );

			if ( $this->prefs['forum_reply']['creds'] != 0 )
				add_action( 'cpc_forum_reply_add_hook', array( $this, 'forum_reply' ), 10, 4 );

			if ( $this->prefs['group_created']['creds'] != 0 )
				add_action( 'cpc_group_created', array( $this, 'group_created' ), 10, 2 );

			if ( $this->prefs['group_joined']['creds'] != 0 )
				add_action( 'cpc_user_joined_group', array( $this, 'group_joined' ), 10, 3 );

			if ( $this->prefs['friendship_accepted']['creds'] != 0 )
				add_action( 'cpc_friends_accept_hook', array( $this, 'friendship_accepted' ), 10, 2 );

			if ( $this->prefs['activity_deleted']['creds'] != 0 || $this->prefs['forum_post_deleted']['creds'] != 0 || $this->prefs['group_deleted']['creds'] != 0 || $this->prefs['group_left']['creds'] != 0 || $this->prefs['friendship_removed']['creds'] != 0 )
				add_action( 'before_delete_post', array( $this, 'post_deleted' ), 10, 2 );

			if ( $this->prefs['activity_comment_deleted']['creds'] != 0 || $this->prefs['forum_reply_deleted']['creds'] != 0 )
				add_action( 'delete_comment', array( $this, 'comment_deleted' ), 10, 2 );

		}

		public function activity_post( $post, $files, $activity_id ) {

			$this->award_for_post( 'activity_post', 'ps_community_activity_post', $activity_id );

		}

		public function activity_comment( $post, $comment_id ) {

			$comment = get_comment( $comment_id );
			if ( ! $comment ) return;

			$this->award( 'activity_comment', 'ps_community_activity_comment', $comment->user_id, $comment_id, array( 'ref_type' => 'comment' ) );

		}

		public function forum_post( $post, $files, $post_id ) {

			$this->award_for_post( 'forum_post', 'ps_community_forum_post', $post_id );

		}

		public function forum_reply( $comment, $files, $post_id, $comment_id ) {

			$reply = get_comment( $comment_id );
			if ( ! $reply ) return;

			$this->award( 'forum_reply', 'ps_community_forum_reply', $reply->user_id, $comment_id, array( 'ref_type' => 'comment' ) );

		}

		public function group_created( $group_id, $user_id ) {

			$this->award( 'group_created', 'ps_community_group_created', $user_id, $group_id, array( 'ref_type' => 'post' ) );

		}

		public function group_joined( $user_id, $group_id, $membership_id ) {

			$this->award( 'group_joined', 'ps_community_group_joined', $user_id, $membership_id, array( 'ref_type' => 'post' ) );

		}

		public function friendship_accepted( $sent_user_id, $accepted_user_id ) {

			$entry_id = implode( ':', array( absint( $sent_user_id ), absint( $accepted_user_id ) ) );
			$this->award( 'friendship_accepted', 'ps_community_friendship_accepted', $accepted_user_id, $entry_id );

		}

		public function post_deleted( $post_id, $post ) {

			if ( ! $post )
				$post = get_post( $post_id );

			if ( ! $post ) return;

			if ( $post->post_type === 'cpc_activity' ) {
				$this->deduct( 'activity_deleted', 'ps_community_activity_deleted', 'ps_community_activity_post', $post->post_author, $post_id, array( 'ref_type' => 'post' ) );
			}
			elseif ( $post->post_type === 'cpc_forum_post' ) {
				$this->deduct( 'forum_post_deleted', 'ps_community_forum_post_deleted', 'ps_community_forum_post', $post->post_author, $post_id, array( 'ref_type' => 'post' ) );
			}
			elseif ( $post->post_type === 'cpc_group' ) {
				$this->deduct( 'group_deleted', 'ps_community_group_deleted', 'ps_community_group_created', $post->post_author, $post_id, array( 'ref_type' => 'post' ) );
			}
			elseif ( $post->post_type === 'cpc_group_members' ) {
				$user_id = get_post_meta( $post_id, 'cpc_member_user_id', true );
				$this->deduct( 'group_left', 'ps_community_group_left', 'ps_community_group_joined', $user_id, $post_id, array( 'ref_type' => 'post' ) );
			}
			elseif ( $post->post_type === 'cpc_friendship' ) {
				$sent_user_id = absint( get_post_meta( $post_id, 'cpc_member1', true ) );
				$accepted_user_id = absint( get_post_meta( $post_id, 'cpc_member2', true ) );

				if ( get_current_user_id() === $accepted_user_id ) {
					$entry_id = implode( ':', array( $sent_user_id, $accepted_user_id ) );
					$this->deduct( 'friendship_removed', 'ps_community_friendship_removed', 'ps_community_friendship_accepted', $accepted_user_id, $entry_id );
				}
			}

		}

		public function comment_deleted( $comment_id, $comment = NULL ) {

			if ( ! $comment )
				$comment = get_comment( $comment_id );

			if ( ! $comment ) return;

			if ( $comment->comment_type === 'cpc_activity_comment' ) {
				$this->deduct( 'activity_comment_deleted', 'ps_community_activity_comment_deleted', 'ps_community_activity_comment', $comment->user_id, $comment_id, array( 'ref_type' => 'comment' ) );
			}
			elseif ( $comment->comment_type === 'cpc_forum_comment' ) {
				$this->deduct( 'forum_reply_deleted', 'ps_community_forum_reply_deleted', 'ps_community_forum_reply', $comment->user_id, $comment_id, array( 'ref_type' => 'comment' ) );
			}

		}

		private function award_for_post( $setting, $reference, $post_id ) {

			$post = get_post( $post_id );
			if ( ! $post ) return;

			$this->award( $setting, $reference, $post->post_author, $post_id, array( 'ref_type' => 'post' ) );

		}

		private function award( $setting, $reference, $user_id, $entry_id, $data = '' ) {

			$user_id = absint( $user_id );
			if ( ! $user_id || ! $entry_id || $this->core->exclude_user( $user_id ) ) return;
			if ( $this->over_hook_limit( $setting, $reference, $user_id ) ) return;
			if ( $this->core->has_entry( $reference, $entry_id, $user_id ) ) return;

			$this->core->add_creds(
				$reference,
				$user_id,
				$this->prefs[ $setting ]['creds'],
				$this->prefs[ $setting ]['log'],
				$entry_id,
				$data,
				$this->bonips_type
			);

		}

		private function deduct( $setting, $reference, $earned_reference, $user_id, $entry_id, $data = '' ) {

			$user_id = absint( $user_id );
			if ( ! $user_id || ! $entry_id || $this->core->exclude_user( $user_id ) ) return;
			if ( ! $this->core->has_entry( $earned_reference, $entry_id, $user_id ) ) return;
			if ( $this->core->has_entry( $reference, $entry_id, $user_id ) ) return;

			$this->core->add_creds(
				$reference,
				$user_id,
				$this->prefs[ $setting ]['creds'],
				$this->prefs[ $setting ]['log'],
				$entry_id,
				$data,
				$this->bonips_type
			);

		}

		public function preferences() {

			$prefs = $this->prefs;
			$settings = array(
				'activity_post'       => __( 'Neuer Community-Beitrag', 'bonips' ),
				'activity_deleted'    => __( 'Community-Beitrag gelöscht', 'bonips' ),
				'activity_comment'    => __( 'Neuer Aktivitätskommentar', 'bonips' ),
				'activity_comment_deleted' => __( 'Aktivitätskommentar gelöscht', 'bonips' ),
				'forum_post'          => __( 'Neues Forenthema', 'bonips' ),
				'forum_post_deleted'  => __( 'Forenthema gelöscht', 'bonips' ),
				'forum_reply'         => __( 'Neue Forenantwort', 'bonips' ),
				'forum_reply_deleted' => __( 'Forenantwort gelöscht', 'bonips' ),
				'group_created'       => __( 'Gruppe erstellt', 'bonips' ),
				'group_deleted'       => __( 'Gruppe gelöscht', 'bonips' ),
				'group_joined'        => __( 'Gruppe beigetreten', 'bonips' ),
				'group_left'          => __( 'Gruppe verlassen', 'bonips' ),
				'friendship_accepted' => __( 'Freundschaft angenommen', 'bonips' ),
				'friendship_removed'  => __( 'Freundschaft beendet', 'bonips' )
			);

			foreach ( $settings as $setting => $label ) :
?>
<div class="hook-instance">
	<h3><?php echo esc_html( $label ); ?></h3>
	<div class="row">
		<div class="col-lg-2 col-md-6 col-sm-12 col-xs-12">
			<div class="form-group">
				<label for="<?php echo $this->field_id( array( $setting, 'creds' ) ); ?>"><?php echo $this->core->plural(); ?></label>
				<input type="text" name="<?php echo $this->field_name( array( $setting, 'creds' ) ); ?>" id="<?php echo $this->field_id( array( $setting, 'creds' ) ); ?>" value="<?php echo $this->core->number( $prefs[ $setting ]['creds'] ); ?>" class="form-control" />
			</div>
		</div>
		<div class="col-lg-4 col-md-6 col-sm-12 col-xs-12">
			<div class="form-group">
				<label for="<?php echo $this->field_id( array( $setting, 'limit' ) ); ?>"><?php _e( 'Limit', 'bonips' ); ?></label>
				<?php echo $this->hook_limit_setting( $this->field_name( array( $setting, 'limit' ) ), $this->field_id( array( $setting, 'limit' ) ), $prefs[ $setting ]['limit'] ); ?>
			</div>
		</div>
		<div class="col-lg-6 col-md-6 col-sm-12 col-xs-12">
			<div class="form-group">
				<label for="<?php echo $this->field_id( array( $setting, 'log' ) ); ?>"><?php _e( 'Protokollvorlage', 'bonips' ); ?></label>
				<input type="text" name="<?php echo $this->field_name( array( $setting, 'log' ) ); ?>" id="<?php echo $this->field_id( array( $setting, 'log' ) ); ?>" value="<?php echo esc_attr( $prefs[ $setting ]['log'] ); ?>" class="form-control" />
				<span class="description"><?php echo $this->available_template_tags( array( 'general' ) ); ?></span>
			</div>
		</div>
	</div>
</div>
<?php
			endforeach;

		}

	}

}
