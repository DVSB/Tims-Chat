<?php
namespace chat\data\suspension;
use \wcf\system\WCF;

/**
 * Represents a chat suspension.
 * 
 * @author 	Tim Düsterhus
 * @copyright	2010-2014 Tim Düsterhus
 * @license	Creative Commons Attribution-NonCommercial-ShareAlike <http://creativecommons.org/licenses/by-nc-sa/3.0/legalcode>
 * @package	be.bastelstu.chat
 * @subpackage	data.suspension
 */
class Suspension extends \chat\data\CHATDatabaseObject {
	/**
	 * @see	\wcf\data\DatabaseObject::$databaseTableName
	 */
	protected static $databaseTableName = 'suspension';
	
	/**
	 * @see	\wcf\data\DatabaseObject::$databaseTableIndexName
	 */
	protected static $databaseTableIndexName = 'suspensionID';
	
	const TYPE_MUTE = 'mute';
	const TYPE_BAN = 'ban';
	
	/**
	 * Returns whether the suspension still is valid.
	 * 
	 * @return	boolean
	 */
	public function isValid() {
		return $this->expires > TIME_NOW;
	}
	
	/**
	 * Returns whether the given user may view this suspension.
	 * 
	 * @param	\wcf\data\user\User	$user
	 * @return	boolean
	 */
	public function isVisible($user = null) {
		if ($user === null) $user = WCF::getUser();
		$user = new \wcf\data\user\UserProfile($user);
		$ph = new \chat\system\permission\PermissionHandler($user->getDecoratedObject());
		
		if ($user->getPermission('admin.chat.canManageSuspensions')) return true;
		if ($user->getPermission('mod.chat.canG'.$this->type)) return true;
		if (!$this->roomID) return false;
		if ($ph->getPermission($this->getRoom(), 'mod.can'.ucfirst($this->type))) return true;
		
		return false;
	}
	
	/**
	 * Returns the room of this suspension.
	 * 
	 * @return	\chat\data\room\Room
	 */
	public function getRoom() {
		if (!$this->roomID) return new \chat\data\room\Room(null, array('roomID' => null));
		
		return \chat\data\room\RoomCache::getInstance()->getRoom($this->roomID);
	}
	
	/**
	 * Returns all the suspensions for the specified user (current user if no user was specified).
	 * 
	 * @param	\wcf\data\user\User	$user
	 * @return	array
	 */
	public static function getSuspensionsForUser(\wcf\data\user\User $user = null) {
		if ($user === null) $user = WCF::getUser();
		$ush = \wcf\system\user\storage\UserStorageHandler::getInstance();
		
		// load storage
		$ush->loadStorage(array($user->userID));
		$data = $ush->getStorage(array($user->userID), 'chatSuspensions');
		
		try {
			$suspensions = unserialize($data[$user->userID]);
			if ($suspensions === false) throw new \wcf\system\exception\SystemException();
		}
		catch (\wcf\system\exception\SystemException $e) {
			$condition = new \wcf\system\database\util\PreparedStatementConditionBuilder();
			$condition->add('userID = ?', array($user->userID));
			$condition->add('expires > ?', array(TIME_NOW));
			
			$sql = "SELECT
					*
				FROM
					chat".WCF_N."_suspension
				".$condition;
			$stmt = WCF::getDB()->prepareStatement($sql);
			$stmt->execute($condition->getParameters());
			
			$suspensions = array();
			while ($suspension = $stmt->fetchObject('\chat\data\suspension\Suspension')) {
				$suspensions[$suspension->roomID][$suspension->type] = $suspension;
			}
			
			$ush->update($user->userID, 'chatSuspensions', serialize($suspensions));
		}
		
		return $suspensions;
	}
	
	/**
	 * Returns the appropriate suspension for user, room and type.
	 * Returns false if no active suspension was found.
	 * 
	 * @param	\wcf\data\user\User	$user
	 * @param	\chat\data\room\Room	$room
	 * @param	integer			$type
	 * @return	\chat\data\suspension\Suspension
	 */
	public static function getSuspensionByUserRoomAndType(\wcf\data\user\User $user, \chat\data\room\Room $room, $type) {
		$condition = new \wcf\system\database\util\PreparedStatementConditionBuilder();
		$condition->add('userID = ?', array($user->userID));
		$condition->add('type = ?', array($type));
		$condition->add('expires > ?', array(TIME_NOW));
		if ($room->roomID) $condition->add('roomID = ?', array($room->roomID));
		else $condition->add('roomID IS NULL');
		
		$sql = "SELECT
				*
			FROM
				chat".WCF_N."_suspension
			".$condition;
		
		$statement = WCF::getDB()->prepareStatement($sql);
		$statement->execute($condition->getParameters());
		$row = $statement->fetchArray();
		if (!$row) return false;
		
		return new self(null, $row);
	}
}
