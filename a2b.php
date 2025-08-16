<?php
	########################################################
	####        Developed And Coded By 𝓐𝓱.𝓚          ####
	####           Contact Us: 0096176012019            ####
	########################################################
	load_game_engine('Village');

	class A2b_Controller extends VillageController {
		private $pageState = null;
		private $artLevel = 0;
		private $targetVillage = ['x' => NULL, 'y' => NULL];
		private $troops = null;
		private $hasHero = false;
		private $troops_last = null;
		private $disableFirstTwoAttack = FALSE;
		private $attackWithCatapult = FALSE;
		private $transferType = 2;
		private $errorTable = [];
		private $newVillageResources = [1 => 187500, 2 => 187500, 3 => 187500, 4 => 187500];
		private $rallyPointLevel = 0;
		private $totalCatapultTroopsCount = 0;
		private $catapultCanAttackLastIndex = 0;
		private $availableCatapultTargetsString = '';
		private $catapultCanAttack = [0 => 0, 1 => 10, 2 => 11, 3 => 9, 4 => 6, 5 => 2, 6 => 4, 7 => 8, 8 => 7, 9 => 3, 10 => 5, 11 => 1, 12 => 22, 13 => 13, 14 => 19, 15 => 46, 16 => 35, 17 => 18, 18 => 29, 19 => 30, 20 => 37, 21 => 41, 22 => 15, 23 => 17, 24 => 26, 25 => 16, 26 => 25, 27 => 20, 28 => 14, 29 => 24, 30 => 28, 31 => 40, 32 => 21, 33 => 27, 34 => 38, 35 => 39];
		private $onlyOneSpyAction = FALSE;
		private $backTroopsProperty = [];

		public function __construct() {
			parent::__construct();
			$this->viewFile = 'a2b';
			$this->viewData['contentCssClass'] = 'a2b';
		}

		public function onLoadBuildings($building) {
			if (($building['item_id'] == 16 and $this->rallyPointLevel < $building['level'])) {
				$this->rallyPointLevel = $building['level'];
			}
		}

		public function index() {
			$this->viewData['pageState'] =& $this->pageState;
			$this->viewData['troops'] =& $this->troops;
			$this->viewData['troops_last'] =& $this->troops_last;
			$this->viewData['errorTable'] =& $this->errorTable;
			$this->viewData['hasHero'] =& $this->hasHero;
			$this->viewData['transferType'] =& $this->transferType;
			$this->viewData['disableFirstTwoAttack'] =& $this->disableFirstTwoAttack;
			$this->viewData['targetVillage'] =& $this->targetVillage;
			$this->viewData['newVillageResources'] =& $this->newVillageResources;
			$this->viewData['attackWithCatapult'] =& $this->attackWithCatapult;
			$this->viewData['backTroopsProperty'] =& $this->backTroopsProperty;
			$this->viewData['rallyPointLevel'] = $this->rallyPointLevel;
			$this->viewData['totalCatapultTroopsCount'] =& $this->totalCatapultTroopsCount;
			$this->viewData['availableCatapultTargetsString'] =& $this->availableCatapultTargetsString;
			$this->viewData['onlyOneSpyAction'] =& $this->onlyOneSpyAction;

			if ($this->rallyPointLevel <= 0) {
				$this->is_redirect = TRUE;
				redirect('build?id=39');
				return null;
			}
			
			if($this->isGameOver()){
				$this->is_redirect = TRUE;
				redirect('over');
				return null;
			}
			
			if (is_post('captcha')) {
				if (post('captcha') != $_SESSION['vercode']) {
					$this->is_redirect = TRUE;
					redirect('build?id=39');
					return null;
				}
			}
			$_SESSION['vercode'] = 0;

			if (((is_get('d1') or is_get('d2')) or is_get('d3'))) {
				$this->pageState = 3;
				$this->handleTroopBack();
				return null;
			}
			
            if (!isset($_SESSION['attack_timestamps'])) {
                $_SESSION['attack_timestamps'] = [];
            }
            
            $currentTime = time();
            
            $_SESSION['attack_timestamps'] = array_filter(
                $_SESSION['attack_timestamps'],
                function ($timestamp) use ($currentTime) {
                    return ($currentTime - $timestamp) <= 45;
                }
            );			
			
			$this->viewData['targetX'] = $_SESSION['targetX'];
			$this->viewData['targetY'] = $_SESSION['targetY'];			
			$this->viewData['transferCookies'] = $_SESSION['transferCookies'];		

			$this->load_model('A2b', 'A');
			$this->pageState = 1;
			$map_size = $this->setupMetadata['map_size'];
			$half_map_size = floor($map_size / 2);
			$this->hasHero = $this->data['hero_in_village_id'] == $this->data['selected_village_id'];
            $GameMetadata = $GLOBALS['GameMetadata'];
            $t_arr = explode('|', $this->data['troops_num']);
            $troops2 = [];
            
            foreach ($t_arr as $t_str) {
                $t2_arr = explode(':', $t_str);
                if ($t2_arr[0] != -1) {
                    continue;
                }
            
                $t2_arr = explode(',', $t2_arr[1]);
                foreach ($t2_arr as $t2_str) {
                    $t = explode(' ', $t2_str);
                    if ($t[0] == 99) {
                        continue;
                    }
            
                    $troopData = [
                        'troopId' => $t[0],
                        'number' => $t[1],
                        'Att' => $GameMetadata['troops'][$t[0]]['attack_value'],
                        'Deff_i' => $GameMetadata['troops'][$t[0]]['defense_infantry'],
                        'Deff_c' => $GameMetadata['troops'][$t[0]]['defense_cavalry'],
                        'Crop' => $GameMetadata['troops'][$t[0]]['crop_consumption'],
                        'Carry' => $GameMetadata['troops'][$t[0]]['carry_load']
                    ];
                    
                    $this->troops[] = $troopData;
                    $troops2[] = ['troopId' => $t[0], 'number' => 0];
                }
            }

			
            $temp1 = $this->A->GetLastReport($this->player->playerId);
            if ($temp1) {
                $temp2 = explode('|', $temp1);
                $temp3 = explode(',', $temp2[0]);
                
                foreach ($temp3 as $t_str) {
                    $t2_arr = explode(',', $t_str);
                    foreach ($t2_arr as $t2_str) {
                        $t = explode(' ', $t2_str);
                        if ($t[0] == 99) {
                            continue;
                        }
                        
                        $this->troops_last[] = ['troopId' => $t[0], 'number' => $t[1]];
                    }
                }
            } else {
                $this->troops_last = $troops2;
            }

			
			$attackOptions1 = '';
			$sendTroops = FALSE;
			$playerData = NULL;
			$villageRow = NULL;

			if (!$_POST) {
				if ((is_get('id') and is_numeric(get('id')))) {
					$vid = intval(get('id'));
					if ($vid < 1) {
						$vid = 1;
					}
					$villageRow = $this->A->getVillageDataById($vid);
				}
			} else {
				if (is_post('id')) {
					$sendTroops = (!$this->isGameTransientStopped() && !$this->isGameOver() && $this->viewData['isGameStarted']);
					$vid = intval(post('id'));
					$villageRow = $this->A->getVillageDataById($vid);
				} else {
					if ((is_post('dname') and trim(post('dname')) != '')) {
						$villageRow = $this->A->getVillageDataByName(post('dname'));
					} else {
						if ((((is_post('x') and is_post('y')) and trim(post('x')) != '') and post('y') != '')) {
							$vid = $this->__getVillageId($map_size, $this->__getCoordInRange($map_size, intval(post('x'))), $this->__getCoordInRange($map_size, intval(post('y'))));
							$villageRow = $this->A->getVillageDataById($vid);
						}
					}
				}
			}
			if ($villageRow == NULL) {
				if ($_POST) {
					$this->errorTable = v2v_p_entervillagedata;
				}
				return null;
			}

			$this->disableFirstTwoAttack = (intval($villageRow['player_id']) == 0 and $villageRow['is_oasis']);
			$this->targetVillage['x'] = floor(($villageRow['id'] - 1) / $map_size);
			$this->targetVillage['y'] = $villageRow['id'] - ($this->targetVillage['x'] * $map_size + 1);
			if ($half_map_size < $this->targetVillage['x']) {
				$this->targetVillage['x'] -= $map_size;
			}
			if ($half_map_size < $this->targetVillage['y']) {
				$this->targetVillage['y'] -= $map_size;
			}
			if ($villageRow['id'] == $this->data['selected_village_id']) {
				return null;
			}
			if ((0 < intval($villageRow['player_id']) and $this->A->getPlayType($villageRow['player_id']) == PLAYERTYPE_ADMIN)) {
				return null;
			}
			$spyOnly = FALSE;
			global $gameConfig;
			if ((!$villageRow['is_oasis'] and intval($villageRow['player_id']) == 0)) {
				$this->transferType = 1;
				$humanTroopIds = [10, 20, 30, 50, 60, 70, 80, 109];
                $humanTroopId = 0;
                $renderTroops = [];
				foreach ($this->troops as $troop) {
					$renderTroops[$troop['troopId']] = 0;
					if (in_array($troop['troopId'], $humanTroopIds)) {
						$humanTroopId = $troop['troopId'];
						$renderTroops[$humanTroopId] = $troop['number'];
						if ($renderTroops[$humanTroopId] >= 3) {
							$renderTroops[$humanTroopId] = 3;
						}
						continue;
					}
				}
				
				
				$canBuildNewVillage = (isset($renderTroops[$humanTroopId]) && 3 <= $renderTroops[$humanTroopId]);
				if ($canBuildNewVillage) {
				    $villagesCount = $this->G->getPlayerVillagesCount($this->player->playerId);
    			    $newVillagesQueue = $this->G->countNewVillageQueues($this->player->playerId);
    			    $totalVillagesWithQueues = $villagesCount + $newVillagesQueue;				    
				    if($totalVillagesWithQueues >= $gameConfig['settings']['max_villages']){
				        $this->errorTable = 'لا يمكنك بناء اكثر من '. $gameConfig['settings']['max_villages'] .' قرية';
				        return null;
				    }
					$count = (trim($this->data['child_villages_id'] ?? '') == '' ? 0 : sizeof(explode(',', $this->data['child_villages_id'])));
					if (1 < $count && !$this->data['is_capital']) {
						$this->errorTable = v2v_p_cannotbuildnewvill;
						return null;
					}
					if (2 < $count && $this->data['is_capital']) {
						$this->errorTable = v2v_p_cannotbuildnewvill;
						return null;
					}
					if (!$this->_canBuildNewVillage()) {
						$this->errorTable = v2v_p_cannotbuildnewvill1;
						return null;
					}
					if (!$this->isResourcesAvailable($this->newVillageResources)) {
						$this->errorTable = sprintf(v2v_p_cannotbuildnewvill2, $this->newVillageResources['1']);
						return null;
					}
					$this->load_model('A2b', 'A');
					if ($this->A->isVillageUnderQueue($villageRow['id'])) {
						$this->errorTable = "هناك مستوطنين من لاعب اخر في طريقهم لإستوطان هذه القرية";
						return null;
					}
				} else {
					$this->errorTable = v2v_p_cannotbuildnewvill4;
					return null;
				}
				$this->pageState = 2;
			} else {
				if ($_POST) {
					if ((!$villageRow['is_oasis'] and intval($villageRow['player_id']) == 0)) {
						$this->errorTable = v2v_p_novillagehere;
						return null;
					}
					if (((!is_post('c') && intval(post('c')) < 1) or 4 < intval(post('c')))) {
						return null;
					}
					$this->transferType = ($this->disableFirstTwoAttack ? 4 : intval(post('c')));

					$this->load_model('A2b', 'A');
					$war9 = $this->A->IfVillageHasAttak($this->data['selected_village_id']);
					if ($war9 >= 960) {
						$this->errorTable = v2v_p_cantattac;
						return null;
					}
					$totalAccountQueues = $this->G->getPlayerQueues($this->player->playerId);
					if($totalAccountQueues >= 3000){
						$this->errorTable = 'تجاوزت الحد المسموح لارسال الهجمات';
						return null;					    
					}					
					if (count($_SESSION['attack_timestamps']) >= 30 && in_array(post('c'), [3, 4])) {
						$this->errorTable = 'تجاوزت عدد الهجمات المسموح خلال دقيقة!';
						return null;					    
					}
					if (0 < intval($villageRow['player_id'])) {
					    if($villageRow['is_art'] && intval(post('c')) == 2){
							$this->errorTable = 'لا يمكنك تعزيز قرى التحف';
							return null;
					    }
				// 		if ($villageRow['player_id'] != $this->player->playerId and $this->player->isAgent and intval(post('c')) == 2) {
				// 			$this->errorTable = v2v_p_isAgent;
				// 			return null;
				// 		}
						$playerData = $this->A->getPlayerDataById(intval($villageRow['player_id']));
						if ($playerData['blocked_second'] > 0 || $playerData['is_blocked']) {
							$this->errorTable = v2v_p_playerwas_blocked;
							return null;
						}
						$samePlayer = $this->player->playerId == intval($villageRow['player_id']);
						$tatarCapital = $villageRow['player_name'] == tatar_tribe_player && $villageRow['is_capital'];

						$this->load_model('Artefacts', 'art');
						$hasArt = $this->art->getArtefactsCountByVillageId($villageRow['id']);
						//To Player Protection
						$isDispersaled = $this->A->isDispersaled(intval($villageRow['id']));
						if ($playerData['protection'] > time() && !$villageRow['is_special_village'] && !$hasArt && !$isDispersaled) {
							if ($this->player->playerId != $villageRow['player_id']) {
								$this->errorTable = v2v_p_playerwas_inprotectedperiod;
								return null;
							}
						}
                    
						
						$sameAlliance = $this->data['alliance_id'] == $playerData['alliance_id'];
						$allianceContracts = $this->A->ifhasContracts($playerData['alliance_id'], $this->data['alliance_id']);
						$canSendContractsRein = $allianceContracts && $gameConfig['settings']['onlyContracts'];
						
						if($gameConfig['settings']['onlyAlliance'] && !$samePlayer && post('c') == 2){
                            if (!$this->data['alliance_id'] || (!$sameAlliance && !$canSendContractsRein)) {
                                $this->errorTable = 'لا يمكنك تعزيز قرى خارج التحالف';
                                return null;
                            }
						}


						if ($this->A->Is_Oasis_Under_Deletion($villageRow['id'])) {
							$this->errorTable = v2v_try_again;
							return null;
						}
						if (!$samePlayer && intval(post('c')) != 2 && !$hasArt) {
							if ($sameAlliance && $this->data['alliance_id']) {
								$this->errorTable = v2v_p_playerwas_inyouralliance;
								return null;
							}
							if ($allianceContracts) {
								$this->errorTable = v2v_p_playerwas_inpeacealliance;
								return null;
							}
						}
						$this->Gsummry = $this->A->GetGsummaryData();
						if ($this->Gsummry['truce_second'] > 0) {
							$this->errorTable = $this->Gsummry['truce_reason'];
							return null;
						}
						if ($villageRow['is_special_village'] && $villageRow['player_name'] == "التتار" && intval(post('c')) == 2) {
							$this->errorTable = v2v_special_not_allowed;
							return null;
						}
						if ($this->data['blocked_second'] > 0 || $this->data['is_blocked']) {
							$this->errorTable = v2v_p_playerwas_youblocked;
							return null;
						}
					}
					$totalTroopsCount = 0;
					$totalTroopsWihtoutHero = 0;
					$totalSpyTroopsCount = 0;
					$this->totalCatapultTroopsCount = 0;
					$hasTroopsSelected = FALSE;
					$renderTroops = [];
					if (is_post('t') || is_post('tro')) {
						foreach ($this->troops as $troop) {
							$num = 0;
							if ((isset($_POST['t'][$troop['troopId']]) and 0 < intval($_POST['t'][$troop['troopId']]))) {
								if (preg_match('/^[+-]?[0-9]+$/', $_POST['t'][$troop['troopId']]) == 0) {
									$this->errorTable = v2v_p_thereisnoattacktroops;
									return null;
									exit;
								}
								$num = ($troop['number'] < $_POST['t'][$troop['troopId']] ? $troop['number'] : intval($_POST['t'][$troop['troopId']]));
							}
							$renderTroops[$troop['troopId']] = $num;
							$totalTroopsCount += $num;
							$totalTroopsWihtoutHero += $num;
							if (0 < $num) {
								$hasTroopsSelected = TRUE;
							}
                            $spyTroopIds = [4, 14, 23, 102, 54, 62, 74, 44];
                            $catapultTroopIds = [8, 18, 28, 107, 58, 68, 78, 48];
                            
                            if (in_array($troop['troopId'], $spyTroopIds)) {
                                $totalSpyTroopsCount += $num;
                                continue;
                            } 
                            
                            if (in_array($troop['troopId'], $catapultTroopIds)) {
                                $this->totalCatapultTroopsCount = $num;
                                continue;
                            }
                            
                            continue;
						}
					}
					if ((($this->hasHero && is_post('_t')) && intval(post('_t')) == 1)) {
						$hasTroopsSelected = TRUE;
						$totalTroopsCount += 1;
					}
					
					$spyOnly = ($totalSpyTroopsCount == $totalTroopsCount) && ($this->transferType == 3 || $this->transferType == 4) && intval($villageRow['player_id']) > 0;

					if ($spyOnly) {
						$this->onlyOneSpyAction = $villageRow['is_oasis'];
					}
                    $this->attackWithCatapult = $this->totalCatapultTroopsCount > 0 && $this->transferType == 3 && intval($villageRow['player_id']) > 0 && !$villageRow['is_oasis'];
                    
                    if ($this->attackWithCatapult) {
                        $this->catapultCanAttackLastIndex = ($this->rallyPointLevel >= 10) ? count($this->catapultCanAttack) - 1 : (($this->rallyPointLevel >= 5) ? 11 : (($this->rallyPointLevel >= 3) ? 2 : 0));
                    
                        $attackOptions1 = is_post('dtg') && $this->_containBuildingTarget(post('dtg')) ? intval(post('dtg')) : 0;
                        if ($this->rallyPointLevel == 20 && $this->totalCatapultTroopsCount >= 20) {
                            $attackOptions1 = '2:' . ($attackOptions1 . ' ' . (is_post('dtg1') && $this->_containBuildingTarget(post('dtg1')) ? intval(post('dtg1')) : 0));
                        } else {
                            $attackOptions1 = '1:' . $attackOptions1;
                        }
                    
                        $this->availableCatapultTargetsString = '';
                        $selectComboTargetOptions = '';
                    
                        $this->availableCatapultTargetsString .= $this->generateSelectComboTargetOptions(range(1, 9), v2v_p_catapult_grp1);
                    
                        $validBuildings1 = [10, 11, 15, 17, 18, 24, 25, 26, 27, 28, 38, 39, 40];
                        $buildid = $villageRow['is_special_village'] ? 40 : 39;
                        $this->availableCatapultTargetsString .= $this->generateSelectComboTargetOptions(range(10, $buildid), v2v_p_catapult_grp2, $validBuildings1);
                    
                        $validBuildings2 = [13, 14, 16, 19, 20, 21, 22, 35, 37, 46];
                        $this->availableCatapultTargetsString .= $this->generateSelectComboTargetOptions(range(13, 46), v2v_p_catapult_grp3, $validBuildings2);
                    }


					if (!$hasTroopsSelected) {
						$this->errorTable = v2v_p_thereisnoattacktroops;
						return null;
					}
					$this->pageState = 2;
				}
			}
			if ($this->pageState == 2) {
				$this->targetVillage['transferType'] = ($this->transferType == 1 ? "<font color='orange'>" . v2v_p_attacktyp1 . "</font>" . ' ' : ($this->transferType == 2 ? "<font color='green'>" . v2v_p_attacktyp2 . "</font>" . ' ' : ($this->transferType == 3 ? "<font color='red'>" . v2v_p_attacktyp3 . "</font>" : ($this->transferType == 4 ? "<font color='red'>" . v2v_p_attacktyp4 . "</font>" : ''))));
				$this->targetVillage['transferTypee'] = $this->transferType;
				if ($villageRow['is_oasis']) {
					$this->targetVillage['villageName'] = ($playerData != NULL ? v2v_p_placetyp1 : v2v_p_placetyp2);
				} else {
					$this->targetVillage['villageName'] = ($playerData != NULL ? $villageRow['village_name'] : v2v_p_placetyp3);
				}
				$this->targetVillage['underPro'] = false;
				
                $samePlayer = $this->player->playerId == intval($villageRow['player_id']);
				$tatarCapital = $villageRow['player_name'] == tatar_tribe_player && $villageRow['is_capital'];
				$isFreeOasis = $villageRow['is_oasis'] && !$villageRow['player_id'];
				
				if (in_array($this->transferType, [3, 4]) && !$samePlayer && !$isFreeOasis) {
				    if($tatarCapital && $this->transferType == 3 || !$tatarCapital){
				        if ($this->data['protection'] > time()) {
				            $this->targetVillage['underPro'] = true;
				            $sendTroops = false;
				        }
				    }
				}			
				
				$this->targetVillage['villageId'] = $villageRow['id'];
				$this->targetVillage['is_oasis'] = $villageRow['is_oasis'];
				$this->targetVillage['playerName'] = ($playerData != NULL ? $playerData['name'] : ($villageRow['is_oasis'] ? v2v_p_monster : ''));
				$this->targetVillage['playerId'] = ($playerData != NULL ? $playerData['id'] : 0);
				$this->targetVillage['troops'] = $renderTroops;
				$this->targetVillage['is_special_village'] = $villageRow['is_special_village'];
				$this->targetVillage['hasHero'] = (((1 < $this->transferType && $this->hasHero) && is_post('_t')) && intval(post('_t')) == 1);
				$this->targetVillage['onlyHero'] = $totalTroopsWihtoutHero <= 0 && $this->targetVillage['hasHero'];
				$distance = getdistance($this->data['rel_x'], $this->data['rel_y'], $this->targetVillage['x'], $this->targetVillage['y'], $this->setupMetadata['map_size'] / 2);
				$_SESSION['targetX'] = $this->targetVillage['x'];
				$_SESSION['targetY'] = $this->targetVillage['y'];
				$_SESSION['transferCookies'] = $this->transferType;
				if ($this->transferType == 1) {
					$time = $this->targetVillage['time'] = 120;
				} else {
				    $speed = $this->_getTheSlowestTroopSpeed($renderTroops);
				    if ($speed <= 0) {
				        $time = 60;
				    }else{
				        $time = $this->targetVillage['time'] = max(intval($distance / $speed * 3600), 60);
				    }
				}

				$this->targetVillage['needed_time'] = date("G:i:s", time() + $time);
				$this->targetVillage['spy'] = $spyOnly;
				if ($this->hasRallyBuilding()) {
					$this->targetVillage['RallyPoint'] = "";
				} else {
					$this->targetVillage['RallyPoint'] = "<font color='red'>(" . v2v_p_RallyPoint . ")</font>";
				}
			}
			if ($sendTroops) {
                $taskType = [
                    1 => QS_CREATEVILLAGE,
                    2 => QS_WAR_REINFORCE,
                    3 => QS_WAR_ATTACK,
                    4 => QS_WAR_ATTACK_PLUNDER
                ][$this->transferType] ?? 0;

                $spyAction = 0;
                if ($spyOnly) {
                    $taskType = QS_WAR_ATTACK_SPY;
                    $spyAction = is_post('spy') && in_array(intval(post('spy')), [1, 2]) ? intval(post('spy')) : 1;
                    $spyAction = $this->onlyOneSpyAction ? 1 : $spyAction;
                }

                $troops = [];
                foreach ($this->targetVillage['troops'] as $tid => $tnum) {
                    $troops[] = $tid . ' ' . $tnum;
                }
                if ($this->targetVillage['hasHero']) {
                    $troops[] = $this->data['hero_troop_id'] . ' -1';
                }
                $troopsStr = implode(',', $troops);
                
				$catapultTargets = $attackOptions1;
				
                $changingHomeConditions = $taskType == QS_WAR_REINFORCE && $this->player->playerId == (int)$villageRow['player_id'] && !$villageRow['is_oasis'] && !$villageRow['is_special_village'];
                $changeHeroHome = is_post('Change_Hero_Home') && $this->targetVillage['hasHero'] && $changingHomeConditions ? 1 : 0;
                $mergeTroops = is_post('merge_troops') && !$this->targetVillage['onlyHero'] && $gameConfig['settings']['merge_troops'] && $this->data['total_gold'] >= $gameConfig['settings']['merge_troops_gold'] && $changingHomeConditions ? 1 : 0;
                if($mergeTroops){
					$this->load_model('Plus', 'p');
					$this->p->DecGoldFromAll($this->data['email'], $gameConfig['settings']['merge_troops_gold']);
					$this->data['total_gold'] -= $gameConfig['settings']['merge_troops_gold'];                    
                }
				
				$carryResources = ($taskType == QS_CREATEVILLAGE ? implode(' ', $this->newVillageResources) : '');
				$procParams = $troopsStr . '|' . ($this->targetVillage['hasHero'] ? 1 : 0) . '|' . $spyAction . '|' . $catapultTargets . '|' . $carryResources . '|||0#' . $changeHeroHome . '##' . $mergeTroops . '';
			
			    
				if($taskType == QS_CREATEVILLAGE){
    			    $this->load_model('Queuejob', 'qj');
    			    $villagesCount = $this->G->getPlayerVillagesCount($this->player->playerId);
    			    $newVillagesQueue = $this->G->countNewVillageQueues($this->player->playerId);
    			    $totalVillagesWithQueues = $villagesCount + $newVillagesQueue;
    			    $neededCpValue = getKey(($totalVillagesWithQueues + 1), $this->gameMetadata['needed_cp_new_villages'], 49000000);	
    			    $villagesCp = $this->A->getVillagesData($this->player->playerId);
    			    $totalCpValue = 0;
        			foreach ($villagesCp as $row) {
        				@list($cpValue, $cpRate) = explode(' ', $row['cp']);
        				$cpRate = (float) $cpRate;
        				$cpValue += round(($row['elapsedTimeInSeconds'] * ($cpRate / 3600)) + ($row['elapsedTimeInSeconds'] * ($cpRate / 3600) * $this->data['command_center_effect'] / 100), 1);
        				$totalCpValue += $cpValue;
        			}
        			
    			    $totalCpValue = floor($totalCpValue);
    			    
                    foreach ($villagesCp as $village) {
                        @list($cpValue, $cpRate) = explode(" ", $village['cp']);
                        $cpValue = (float)$cpValue;
                        $cpRate = (float)$cpRate;           
                        $cpValue += round(($village['elapsedTimeInSeconds'] * ($cpRate / 3600)) + ($village['elapsedTimeInSeconds'] * ($cpRate / 3600) * $this->data['command_center_effect'] / 100), 1);
                        
                        $proportionalDiscount = ($totalCpValue > 0) ? ($cpValue / $totalCpValue) * $neededCpValue : 0;
                        $remainingPoints = $cpValue - $proportionalDiscount;
                        $resultArr = $this->qj->_getResourcesArray($village, $village['resources'], $village['elapsedTimeInSeconds'], $village['crop_consumption'], $village['cp']);
                        $this->A->updateVillage(intval($village['id']), $this->qj->_getResourcesString($resultArr['resources']), $remainingPoints . " " . $cpRate);
                        
                    }						    
				}
				if(in_array($taskType, [QS_WAR_ATTACK, QS_WAR_ATTACK_PLUNDER])){
				    $_SESSION['attack_timestamps'][] = time();
				}
				$this->load_library('QueueTask', 'newTask', [
					'taskType' => $taskType,
					'playerId' => $this->player->playerId,
					'executionTime' => $gameConfig['system']['environment'] == 'production' ? $time : 5
				]);
				$this->newTask->villageId = $this->data['selected_village_id'];
				$this->newTask->toPlayerId = intval($villageRow['player_id']);
				$this->newTask->toVillageId = $villageRow['id'];
				$this->newTask->procParams = $procParams;
				$this->newTask->tag = [
					'troops' => $this->targetVillage['troops'],
					'hasHero' => $this->targetVillage['hasHero'],
					'resources' => ($taskType == QS_CREATEVILLAGE ? $this->newVillageResources : NULL)
				];
				 //for($i = 0; $i <= 340; $i++){
				$this->queueModel->addTask($this->newTask);
				 //}
				$this->load_model('A2b', 'A');
				$isTaTarVillage = $this->A->isTaTarVillage($villageRow['id']);
				if ($isTaTarVillage && in_array($taskType, [QS_WAR_ATTACK, QS_WAR_ATTACK_PLUNDER])) {
					$tatarCapitalId = $this->A->getTatarCapitalId(intval($villageRow['player_id']));
					$procParamsTaTar =

						'41 ' . mt_rand(10000000, 13000000) . ',' .

						'42 ' . mt_rand(10000000, 13000000) . ',' .

						'43 ' . mt_rand(10000000, 13000000) . ',' .

						'44 0,' .

						'45 ' . mt_rand(7000000, 8000000) . ',' .

						'46 ' . mt_rand(7000000, 8000000) . ',' .

						'47 ' . mt_rand(800000, 900000) . ',' .

						'48 ' . mt_rand(800000, 900000) . ',' .

						'49 0,' .

						'50 0|0|0|1:40||||0';

					$this->load_library('QueueTask', 'newTaskTaTar', [
						'taskType' => QS_WAR_ATTACK,
						'playerId' => intval($villageRow['player_id']),
						'executionTime' => 90
					]);
					$this->newTaskTaTar->villageId = $tatarCapitalId;
					$this->newTaskTaTar->toPlayerId = $this->player->playerId;
					$this->newTaskTaTar->toVillageId = $this->data['selected_village_id'];
					$this->newTaskTaTar->procParams = $procParamsTaTar;
					$this->newTaskTaTar->tag = [
						'troops' => NULL,
						'hasHero' => NULL,
						'resources' => NULL
					];
					$this->queueModel->addTask($this->newTaskTaTar);

				}
				$this->is_redirect = TRUE;
				redirect('build?id=39&u=2');
			}
            if (count($_SESSION['attack_timestamps']) > 100) {
                $_SESSION['attack_timestamps'] = array_slice($_SESSION['attack_timestamps'], -100);
            }			
		}

		private function handleTroopBack() {
		    global $gameConfig;
			$qstr = '';
			$fromVillageId = 0;
			$toVillageId = 0;
			$action = 0;

			if (is_get('d1')) {
				$action = 1;
				$qstr = 'd1=' . intval(get('d1'));
				if (is_get('o')) {
					$qstr .= '&o=' . intval(get('o'));
					$fromVillageId = intval(get('o'));
				} else {
					$fromVillageId = $this->data['selected_village_id'];
				}
				$toVillageId = intval(get('d1'));
			} else {
				if (is_get('d2')) {
					$action = 2;
					$qstr = 'd2=' . intval(get('d2'));
					$fromVillageId = $this->data['selected_village_id'];
					$toVillageId = intval(get('d2'));
				} else {
					if (is_get('d3')) {
						$action = 3;
						$qstr = 'd3=' . intval(get('d3'));
						$fromVillageId = intval(get('d3'));
						$toVillageId = $this->data['selected_village_id'];
					} else {
						$this->is_redirect = TRUE;
						redirect('build?id=39');
					}
				}
			}
			$this->backTroopsProperty['queryString'] = $qstr;
			$this->load_model('A2b', 'A');
			$fromVillageData = $this->A->getVillageData2ById($fromVillageId);
			$toVillageData = $this->A->getVillageData2ById($toVillageId);
			if (($fromVillageData == NULL or $toVillageData == NULL)) {
				$this->is_redirect = TRUE;
				redirect('build?id=39');
			}
			$vid = $toVillageId;
			$_backTroopsStr = '';
			$this->backTroopsProperty['headerText'] = v2v_p_backtroops;
			$this->backTroopsProperty['action1'] = '<a href="dorf3?id=' . $fromVillageData['id'] . '">' . $fromVillageData['village_name'] . '</a>';
			$this->backTroopsProperty['action2'] = '<a href="spieler?uid=' . $fromVillageData['player_id'] . '">' . v2v_p_troopsinvillagenow . '</a>';
			$column1 = '';
			$column2 = '';
			if ($action == 1) {
				$_backTroopsStr = $fromVillageData['troops_num'];
				$column1 = 'troops_num';
				$column2 = 'troops_out_num';
			} else {
				if ($action == 2) {
					$this->backTroopsProperty['headerText'] = v2v_p_backcaptivitytroops;
					$_backTroopsStr = $fromVillageData['troops_intrap_num'];
					$column1 = 'troops_intrap_num';
					$column2 = 'troops_out_intrap_num';
				} else {
					if ($action == 3) {
						$_backTroopsStr = $toVillageData['troops_out_num'];
						$vid = $fromVillageId;
						$column1 = 'troops_num';
						$column2 = 'troops_out_num';
					}
				}
			}
			$this->backTroopsProperty['backTroops'] = $this->_getTroopsForVillage($_backTroopsStr, $vid);
			if ($this->backTroopsProperty['backTroops'] == NULL) {
				$this->is_redirect = TRUE;
				redirect('build?id=39');
			}
			$distance = getdistance($fromVillageData['rel_x'], $fromVillageData['rel_y'], $toVillageData['rel_x'], $toVillageData['rel_y'], $this->setupMetadata['map_size'] / 2);
			if ($_POST) {
				$canSend = FALSE;
				$troopsGoBack = [];
				foreach ($this->backTroopsProperty['backTroops']['troops'] as $tid => $tnum) {
					if ((is_post('t') && isset($_POST['t'][$tid]))) {
						$selNum = intval($_POST['t'][$tid]);
						if ($selNum < 0) {
							$selNum = 0;
						}
						if ($tnum < $selNum) {
							$selNum = $tnum;
						}
						$troopsGoBack[$tid] = $selNum;
						if (0 < $selNum) {
							$canSend = TRUE;
							continue;
						}
						continue;
					} else {
						$troopsGoBack[$tid] = 0;
						continue;
					}
				}
				$sendTroopsArray = [
					'troops' => $troopsGoBack,
					'hasHero' => FALSE,
					'heroTroopId' => 0
				];
				$hasHeroTroop = (($this->backTroopsProperty['backTroops']['hasHero'] and is_post('_t')) and intval(post('_t')) == 1);
				if ($hasHeroTroop) {
					$sendTroopsArray['hasHero'] = TRUE;
					$sendTroopsArray['heroTroopId'] = $this->backTroopsProperty['backTroops']['heroTroopId'];
					$canSend = TRUE;
				}
				if (!$canSend) {
					$this->is_redirect = TRUE;
					redirect('build?id=39');
				}
				if ((!$this->isGameTransientStopped() && !$this->isGameOver())) {
					$troops1 = $this->_getTroopsAfterReduction($fromVillageData[$column1], $toVillageId, $sendTroopsArray);
					$troops2 = $this->_getTroopsAfterReduction($toVillageData[$column2], $fromVillageId, $sendTroopsArray);
					$this->A->backTroopsFrom($fromVillageId, $column1, $troops1, $toVillageId, $column2, $troops2);
					$timeInSeconds = intval($distance / $this->_getTheSlowestTroopSpeed2($sendTroopsArray) * 3600);
					$procParams = $this->_getTroopAsString($sendTroopsArray) . '|0||||||1#1';

					$this->load_library('QueueTask', 'newTask', [
						'taskType' => QS_WAR_REINFORCE,
						'playerId' => intval($fromVillageData['player_id']),
						'executionTime' => $gameConfig['system']['environment'] == 'production' ? max($timeInSeconds, 30) : 5,
					]);
					$this->newTask->villageId = $fromVillageId;
					$this->newTask->toPlayerId = intval($toVillageData['player_id']);
					$this->newTask->toVillageId = $toVillageId;
					$this->newTask->procParams = $procParams;
					$this->newTask->tag = [
						'troops' => NULL,
						'hasHero' => FALSE,
						'resources' => NULL
					];
					$this->newTask->tag['troopsCropConsume'] = $this->_getTroopCropConsumption($sendTroopsArray);
				    $this->queueModel->addTask($this->newTask);
                    $this->load_model('Queuejob', 'q');
                    $this->load_model('A2b', 'A2');
                    function processVillageUpdate($villageData, $queueJobModel, $actionModel) {
                        $resultArr = $queueJobModel->_getResourcesArray1(
                            $villageData,
                            $villageData['resources'],
                            $villageData['elapsedTimeInSeconds'],
                            $villageData['crop_consumption'],
                            $villageData['cp']
                        ); 
                    
                        $resourcesString = $queueJobModel->_getResourcesString($resultArr['resources']);
                        $cpString = implode(" ", [
                            $resultArr['cp']['cpValue'],
                            $resultArr['cp']['cpRate']
                        ]);
                    
                        $actionModel->updateVillage($villageData['id'], $resourcesString, $cpString);
                    }
                    processVillageUpdate($fromVillageData, $this->q, $this->A2);
                    processVillageUpdate($toVillageData, $this->q, $this->A2);
				 	$this->is_redirect = TRUE;
				 	redirect('build?id=39');
				}
			} else {
				$this->backTroopsProperty['time'] = intval($distance / $this->_getTheSlowestTroopSpeed2($this->backTroopsProperty['backTroops']) * 3600);
			}

		}

		private function _getTroopsForVillage($troopString, $villageId) {
			if (trim($troopString) == '') {
				return NULL;
			}
			$t_arr = explode('|', $troopString);
			foreach ($t_arr as $t_str) {
				$t2_arr = explode(':', $t_str);
				if ($t2_arr[0] == $villageId) {
					$troopTable = [
						'hasHero' => FALSE,
						'heroTroopId' => 0,
						'troops' => []
					];
					$t2_arr = explode(',', $t2_arr[1]);
					foreach ($t2_arr as $t2_str) {
						list($tid, $tnum) = explode(' ', $t2_str);
						if ($tid == 99) {
							continue;
						}
						if ($tnum == 0 - 1) {
							$troopTable['heroTroopId'] = $tid;
							$troopTable['hasHero'] = TRUE;
							continue;
						}
						$troopTable['troops'][$tid] = $tnum;
					}
					return $troopTable;
				}
			}
		}

		private function _getTroopsAfterReduction($troopString, $targetVillageId, $sendTroopsArray): string {
			if (trim($troopString) == '') {
				return '';
			}
			$reductionTroopsString = '';
			$t_arr = explode('|', $troopString);
			foreach ($t_arr as $t_str) {
				$t2_arr = explode(':', $t_str);
				if ($t2_arr[0] == $targetVillageId) {
					$completelyBacked = TRUE;
					$newTroopStr = '';
					$t2_arr = explode(',', $t2_arr[1]);
					foreach ($t2_arr as $t2_str) {
						list($tid, $tnum) = explode(' ', $t2_str);
						if ($tnum == 0 - 1) {
							if (!$sendTroopsArray['hasHero']) {
								if ($newTroopStr != '') {
									$newTroopStr .= ',';
								}
								$newTroopStr .= $tid . ' ' . $tnum;
								$completelyBacked = FALSE;
								continue;
							}
							continue;
						} else {
							if (isset($sendTroopsArray['troops'][$tid])) {
								$n = max(0, $sendTroopsArray['troops'][$tid]);
								if ($tnum < $n) {
									$n = $tnum;
								}
								$tnum -= $n;
								if (0 < $tnum) {
									$completelyBacked = FALSE;
								}
							}
							if ($newTroopStr != '') {
								$newTroopStr .= ',';
							}
							$newTroopStr .= $tid . ' ' . $tnum;
							continue;
						}
					}
					if (!$completelyBacked) {
						if ($reductionTroopsString != '') {
							$reductionTroopsString .= '|';
						}
						$reductionTroopsString .= $targetVillageId . ':' . $newTroopStr;
						continue;
					}
					continue;
				} else {
					if ($reductionTroopsString != '') {
						$reductionTroopsString .= '|';
					}
					$reductionTroopsString .= $t_str;
					continue;
				}
			}
			return $reductionTroopsString;
		}

		private function _getTheSlowestTroopSpeed2($troopsArray) {
			$minSpeed = 0 - 1;
			foreach ($troopsArray['troops'] as $tid => $num) {
				if (0 < $num) {
					$speed = $this->gameMetadata['troops'][$tid]['velocity'];
					if (($minSpeed == 0 - 1 or $speed < $minSpeed)) {
						$minSpeed = $speed;
						continue;
					}
					continue;
				}
			}

			if ($troopsArray['hasHero']) {
				$htid = $troopsArray['heroTroopId'] ? $troopsArray['heroTroopId'] : 0;
				$speed = $this->gameMetadata['troops'][$htid]['velocity'];
				if (($minSpeed == 0 - 1 or $speed < $minSpeed)) {
					$minSpeed = $speed;
				}
			}
			$blvl = $this->_getMaxBuildingLevel(14);
			$factor = ($blvl == 0 ? 100 : $this->gameMetadata['items'][14]['levels'][$blvl - 1]['value']);
			$factor *= $this->gameMetadata['game_speed'] * $this->Artefacts();
			return $minSpeed * ($factor / 100);
		}

		private function _getMaxBuildingLevel($itemId) {
			$result = 0;
			foreach ($this->buildings as $villageBuild) {
				if (($villageBuild['item_id'] == $itemId and $result < $villageBuild['level'])) {
					$result = $villageBuild['level'];
					continue;
				}
			}
			return $result;
		}

		private function Artefacts() {
			$this->load_model('Artefacts', 'A');
			$hugeArt = $this->A->getHugeArtefact($this->player->playerId);
			if($hugeArt){
			    return 2;
			}
			$artLevel = $this->A->Artefacts($this->player->playerId, $this->data['selected_village_id'], 3);
			return ($artLevel == 0) ? 1 : (($artLevel == 1) ? 2 : (($artLevel == 2) ? 1.5 : 2));
		}

		private function _getTroopAsString($troopsArray): string {
			$str = '';
			foreach ($troopsArray['troops'] as $tid => $num) {
				if ($str != '') {
					$str .= ',';
				}
				$str .= $tid . ' ' . $num;
			}
			if ($troopsArray['hasHero']) {
				if ($str != '') {
					$str .= ',';
				}
				$str .= $troopsArray['heroTroopId'] . ' -1';
			}
			return $str;
		}

		private function _getTroopCropConsumption($troopsArray) {
			$consume = 0;
			foreach ($troopsArray['troops'] as $tid => $tnum) {
				$consume += $this->gameMetadata['troops'][$tid]['crop_consumption'] * $tnum;
			}
			if ($troopsArray['hasHero']) {
				$consume += $this->gameMetadata['troops'][$troopsArray['heroTroopId']]['crop_consumption'];
			}
			return $consume;
		}

		private function __getVillageId($map_size, $x, $y) {
			return $x * $map_size + ($y + 1);
		}

		private function __getCoordInRange($map_size, $x) {
			if ($map_size <= $x) {
				$x -= $map_size;
			} else {
				if ($x < 0) {
					$x = $map_size + $x;
				}
			}
			return $x;
		}

		private function _canBuildNewVillage(): bool {
			$neededCpValue = $totalCpValue = 0;

			$this->load_model('Build', 'B');
			$result = $this->B->getVillagesCp($this->data['villages_id']);

			foreach ($result as $row) {
				list($cpValue, $cpRate) = explode(' ', $row['cp']);
				$cpRate = (float) $cpRate;
				$cpValue = (float) $cpValue;
				$cpValue += round(($row['elapsedTimeInSeconds'] * ($cpRate / 3600)) + ($row['elapsedTimeInSeconds'] * ($cpRate / 3600) * $this->data['command_center_effect'] / 100), 1);
				$totalCpValue += $cpValue;
			}
			$villagesCount = $this->G->getPlayerVillagesCount($this->player->playerId);
			$newVillagesQueue = $this->G->countNewVillageQueues($this->player->playerId);
			$totalVillagesWithQueues = $villagesCount + $newVillagesQueue;			
			$neededCpValue = getKey(($totalVillagesWithQueues + 1), $this->gameMetadata['needed_cp_new_villages'], 49000000);
			$totalCpValue = floor($totalCpValue);
			return $neededCpValue <= $totalCpValue;
		}

		private function _containBuildingTarget($item_id): bool {
			$i = 0;
			while ($i <= $this->catapultCanAttackLastIndex) {
				if ($this->catapultCanAttack[$i] == $item_id) {
					return TRUE;
				}
				++$i;
			}
			return FALSE;
		}

		private function _getTheSlowestTroopSpeed($troopsArray) {
			$minSpeed = 0 - 1;
			foreach ($troopsArray as $tid => $num) {
				if (0 < $num) {
					$speed = $this->gameMetadata['troops'][$tid]['velocity'];
					if (($minSpeed == 0 - 1 or $speed < $minSpeed)) {
						$minSpeed = $speed;
						continue;
					}
					continue;
				}
			}
			$heroSpeed = 1;
			if ((($this->hasHero and is_post('_t')) and intval(post('_t')) == 1)) {
				$htid = $this->data['hero_troop_id'] ?? 0;
				$speed = $this->gameMetadata['troops'][$htid]['velocity'];
				if (($minSpeed == 0 - 1 or $speed < $minSpeed)) {
					$minSpeed = $speed;
				}
			}
			$heroActive = !$this->data['hero_Died'] && $this->data['hero_village_features'] == $this->data['selected_village_id'];
			if ($heroActive) {
				$heroSpeed = $this->data['hero_speed_eff'];
				if($this->data['hero_level'] >= 1000){
				    $heroSpeed += 30;
				}
			}			
			$blvl = $this->_getMaxBuildingLevel(14);
			$factor = ($blvl == 0 ? 100 : $this->gameMetadata['items'][14]['levels'][$blvl - 1]['value']);
			$factor *= $this->gameMetadata['game_speed'] * $this->Artefacts();
			$factor += ($factor * $heroSpeed) / 100;
			return $minSpeed * ($factor / 100);
		}

		private function hasRallyBuilding(): bool {
			$b_arr = explode(",", $this->data['buildings']);
			foreach ($b_arr as $b_str) {
				$b2 = explode(" ", $b_str);
				if (!($b2[0] == 14)) {
					continue;
				}
				return TRUE;
			}
			return FALSE;
		}
		
        private function generateSelectComboTargetOptions($range, $label, $validBuildings = []) {
            $selectComboTargetOptions = '';
            foreach ($range as $i) {
                if (empty($validBuildings) || in_array($i, $validBuildings)) {
                    if ($this->_containBuildingTarget($i)) {
                        $selectComboTargetOptions .= sprintf('<option value="%s">%s</option>', $i, constant('item_' . $i));
                    }
                }
            }
            return $selectComboTargetOptions ? '<optgroup label="' . $label . '">' . $selectComboTargetOptions . '</optgroup>' : '';
        }		

	}