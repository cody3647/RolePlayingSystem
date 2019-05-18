<?php

/**
 * Editing and display of character profiles.
 *
 * @package Role Playing System
 * @version 1.0
 * @author Cody Williams <williams.c@gmail.com>
 * @copyright Cody Williams
 * @license BSD http://opensource.org/licenses/BSD-3-Clause
 */

use ElkArte\Errors\ErrorContext;

class CharacterBiography_Controller extends Action_Controller
{
	/**
	 * Member id for the history being viewed
	 * @var int
	 */
	private $_memID = 0;
	
	private $_charID = 0;
	
	private $_saving;
	
	
	/**
	 * Called before all other methods when coming from the dispatcher or
	 * action class.
	 *
	 * - If you initiate the class outside of those methods, call this method.
	 * or setup the class yourself or fall awaits.
	 */
	public function pre_dispatch()
	{
		global $context, $user_info, $memberContext, $user_profile, $cur_profile;

		require_once(SUBSDIR . '/Character.subs.php');
		require_once(SUBSDIR . '/Menu.subs.php');
		require_once(SUBSDIR . '/Profile.subs.php');

		$this->_charID = $this->_req->getQuery('c', 'intval', 0);
		$this->_memID = memberID($this->_charID);
		
		loadMemberContext($this->_memID);
		$context['member'] = &$memberContext[$this->_memID];
		$context['character'] = &$memberContext[$this->_memID]['characters'][$this->_charID];
		
		$cur_profile = $user_profile[$this->_memID]['characters'][$this->_charID];
		$context['id_member'] = $this->_memID;
		$context['id_character'] = $this->_charID;
		
		if (!isset($context['user']['is_owner']))
			$context['user']['is_owner'] = in_array($this->_charID, $user_info['characters']);
		
		loadLanguage('Profile');
		loadLanguage('RolePlayingSystem');
	}
	
	/**
	 * Allow the change or view of profiles.
	 *
	 * - Fires the pre_load event
	 *
	 * @see Action_Controller::action_index()
	 */
	public function action_index()
	{
	}
	
	public function action_biography_edit()
	{
		global $context, $user_info, $txt, $scripturl;
		
		loadTemplate('RpsCharacter');
		loadTemplate('GenericControls');
		require_once(SUBSDIR . '/Editor.subs.php');		
		
		$this->_saving = $this->_req->getPost('save', 'trim', $this->_req->getQuery('save', 'trim', false));
		
		if($this->_saving)
			redirectExit($scripturl . '?action=character;area=summary');
		
		
		$context['becomes_approved'] = false;
		if (allowedTo('rps_bio_approved'))
			$context['becomes_approved'] = true;
		$context['destination'] = 'character;area=biography_edit';
		$context['page_title'] = $context['character']['name'] . ' - Edit Biography';
		
		$editorOptions = array(
			'id' => 'rps_bio',
			'value' => 'OLD BIO',
			'labels' => array(
				'post_button' => $txt['rps_bio_save'],
			),
			// add height and width for the editor
			'height' => '350px',
			'width' => '100%',
			'disable_smiley_box' => true,
			// We do XML preview here.
			'preview_type' => 2
		);
		create_control_richedit($editorOptions);
		return;
	}
}