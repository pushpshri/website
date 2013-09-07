<?php
namespace Destiny\Action\Web\Fantasy\Team;

use Destiny\Common\Service\Fantasy\TeamService;
use Destiny\Common\Service\Fantasy\ChampionService;
use Destiny\Common\Utils\Http;
use Destiny\Common\MimeType;
use Destiny\Common\Session;
use Destiny\Common\Config;
use Destiny\Common\Exception;
use Destiny\Common\Annotation\Action;
use Destiny\Common\Annotation\Route;
use Destiny\Common\Annotation\HttpMethod;
use Destiny\Common\Annotation\Secure;

/**
 * @Action
 */
class Update {

	/**
	 * @Route ("/fantasy/team/update")
	 * @Secure ({"USER"})
	 *
	 * @param array $params
	 * @throws Exception
	 */
	public function execute(array $params) {
		$response = array (
			'success' => false,
			'data' => array (),
			'message' => '' 
		);
		if (! isset ( $params ['champions'] ) || ! isset ( $params ['teamId'] )) {
			throw new Exception ( 'Invalid request' );
		}
		$team = $this->updateTeam ( $response, $params );
		$response ['data'] = array ();
		$response ['data'] ['team'] = $team;
		$response ['data'] ['champions'] = TeamService::instance ()->getTeamChamps ( $team ['teamId'] );
		$response ['success'] = true;
		Http::header ( Http::HEADER_CONTENTTYPE, MimeType::JSON );
		Http::sendString ( json_encode ( $response ) );
	}

	private function updateTeam(array &$response, array $params) {
		$teamService = TeamService::instance ();
		$champService = ChampionService::instance ();
		
		// Get team - Make sure this is one of the users teams
		$team = $teamService->getTeamById ( ( int ) $params ['teamId'] );
		if (empty ( $team )) {
			throw new Exception ( 'Team not found' );
		}
		// Security
		if (Session::getCredentials ()->getUserId () != $team ['userId']) {
			throw new Exception ( 'Update team failed user does not have rights to this team.' );
		}
		
		// Get the users unlocked champs
		$userChampions = ChampionService::instance ()->getUserChampions ( Session::getCredentials ()->getUserId () );
		$userChampionIds = array ();
		foreach ( $userChampions as $userChamp ) {
			$userChampionIds [$userChamp ['championId']] = $userChamp ['championName'];
		}
		
		$championsParam = explode ( ',', $params ['champions'] );
		
		// Load champs
		$newChamps = $champService->getChampionsById ( $championsParam );
		$oldChamps = $teamService->getTeamChamps ( ( int ) $params ['teamId'] );
		$newChampsId = $oldChampsId = array ();
		foreach ( $newChamps as $champ ) {
			$newChampsId [] = $champ ['championId'];
			// Check if champions have been unlocked || if the champ is currently free
			if (! isset ( $userChampionIds [$champ ['championId']] ) && $champ ['championFree'] == '0') {
				throw new Exception ( 'Champion "' . $userChamp ['championName'] . '" not unlocked' );
			}
		}
		foreach ( $oldChamps as $champ ) {
			$oldChampsId [] = $champ ['championId'];
		}
		
		// Not enough champs / Too many champs on the new team size
		$this->checkTeamSize ( count ( $newChamps ) );
		
		$removedChamps = $addedChamps = array ();
		$addedChamps = array_diff ( $newChampsId, $oldChampsId );
		$removedChamps = array_diff ( $oldChampsId, $newChampsId );
		
		// Calculate the transfer offset, when free champs rotate out, you can have illegal champs in your team, which should not cost.
		$transferOffset = $transferDebit = 0;
		foreach ( $removedChamps as $removedId ) {
			foreach ( $oldChamps as $champ ) {
				// If the champion is free and NOT UNLOCKED (illegal champ in team)
				if ((intval ( $champ ['championId'] ) == intval ( $removedId )) && ($champ ['unlocked'] == '0' && $champ ['championFree'] == '0')) {
					++ $transferOffset;
				}
			}
		}
		
		// How many transfers occurred
		$transferDebit = (count ( $addedChamps ) - (Config::$a ['fantasy'] ['team'] ['maxChampions'] - count ( $oldChamps ))) - $transferOffset;
		
		// Check & debit transfers
		if (intval ( $team ['transfersRemaining'] ) < $transferDebit) {
			throw new Exception ( 'No available transfers' );
		}
		$team ['transfersRemaining'] = intval ( $team ['transfersRemaining'] ) - $transferDebit;
		
		// Update transfers
		foreach ( $addedChamps as $addedId ) {
			foreach ( $newChamps as $champ ) {
				if ($champ ['championId'] == $addedId) {
					// TRANSFER IN;
					$teamService->transferIn ( $team, $champ );
					continue;
				}
			}
		}
		foreach ( $removedChamps as $removedId ) {
			foreach ( $oldChamps as $champ ) {
				if ($champ ['championId'] == $removedId) {
					// TRANSFER OUT;
					$teamService->transferOut ( $team, $champ );
					continue;
				}
			}
		}
		$teamService->updateTeamResources ( $team );
		$teamService->updateChampionOrders ( $params ['teamId'], $championsParam );
		return $team;
	}

	private function checkTeamSize($size) {
		$min = ( int ) Config::$a ['fantasy'] ['team'] ['minChampions'];
		$max = ( int ) Config::$a ['fantasy'] ['team'] ['maxChampions'];
		// Not enough champs
		if ($size < $min) {
			throw new Exception ( 'Min [' . $min . '-' . $max . '] ' . $size . ' champions limit' );
		}
		// Too many champs
		if ($size > $max) {
			throw new Exception ( 'Max [' . $min . '-' . $max . '] ' . $size . ' champions limit' );
		}
	}

}