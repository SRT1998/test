<?php
    ########################################################
    ####        Developed And Coded By 𝓐𝓱.𝓚          ####
    ####           Contact Us: 0096176012019            ####
    ########################################################
    load_game_engine('Auth');
    load_game_engine('Berichte', 'Helper');

    class Berichte_Controller extends AuthController {

        private $showList = null;
        private $selectedTabIndex = null;
        private $reportData = null;
        private $dataList = null;
        private $playerRie = null;
        private $pageSize = 10;
        private $pageCount = null;
        private $pageIndex = null;

        public function __construct() {
            parent::__construct();
            $this->viewFile = 'berichte';
            $this->viewData['contentCssClass'] = 'reports';
        }

        public function index() {
            $this->showList = !(is_get('id') && 0 < intval(get('id')));
            $this->Url = $_SERVER["HTTP_HOST"];
            $this->selectedTabIndex = ((((($this->showList && is_get('t')) && is_numeric(get('t'))) && 1 <= intval(get('t'))) && intval(get('t')) <= 7) ? intval(get('t')) : 0);
            $this->load_model('Berichte', 'B');
            $this->B->DeleteOldRpts();
            if ($this->data['new_report_count'] >= 1 && !$this->player->isSpy) {
                $this->B->CleanReportsCount($this->player->playerId);
            }
            if (!$_POST) {
                global $gameConfig;
                global $serv;
                if (!$this->showList) {
                    $this->selectedTabIndex = 0;
                    $reportId = intval(get('id'));
                    $result = $this->B->getReport($reportId);
                    if ($result) {
                        $readStatus = $result['read_status'];
                        $this->reportData = [];
                        $this->reportData['id'] = intval(get('id'));
                        $this->reportData['messageDate'] = date('m/d/Y', $result['creation_date']);
                        $this->reportData['messageTime'] = date('H:i:s', $result['creation_date']);
                        $this->reportData['from_player_id'] = $from_player_id = intval($result['from_player_id']);
                        $this->reportData['to_player_id'] = $to_player_id = intval($result['to_player_id']);
                        $this->reportData['from_village_id'] = intval($result['from_village_id']);
                        $this->reportData['to_village_id'] = intval($result['to_village_id']);
                        $this->reportData['from_player_name'] = $result['from_player_name'];
                        $this->reportData['to_player_name'] = $result['to_player_name'];
                        $this->reportData['to_village_name'] = $result['to_village_name'];
                        $this->reportData['from_village_name'] = $result['from_village_name'];
                        $this->reportData['rpt_body'] = $result['rpt_body'];
                        $this->reportData['rpt_cat'] = $result['rpt_cat'];
                        $this->reportData['mdate'] = date('Y-m-d', $result['creation_date']);
                        $this->reportData['mtime'] = date('H:i:s', $result['creation_date']);
                        $this->reportData['time_ago'] = $result['creation_date'];
                        $this->reportData['to_player_alliance_id'] = $this->B->getPlayerAllianceId($to_player_id);
                        $this->reportData['to_player_tribe_id'] = $this->B->GetTribeId($to_player_id);
                        $this->reportData['from_hash'] = $from_hash = $result['from_player_hash'];
                        $this->reportData['carry_rate'] = $result['carry_rate'];
                        $this->reportData['is_oasis'] = $this->B->isVillageOasis(intval($result['to_village_id']));
                        switch ($this->reportData['rpt_cat']) {
                            case 1:
                                $this->reportData['resources'] = explode(' ', $this->reportData['rpt_body']);
                                break;
                            case 2:
                                list($troopsStr, $this->reportData['cropConsume']) = explode('|', $this->reportData['rpt_body']);
                                $this->reportData['troopsTable'] = ['troops' => [], 'hasHero' => FALSE];
                                $troopsStrArr = explode(',', $troopsStr);
                                foreach ($troopsStrArr as $t) {
                                    list($tid, $tnum) = explode(' ', $t);
                                    if ($tnum == 0 - 1) {
                                        $this->reportData['troopsTable']['hasHero'] = TRUE;
                                    } else {
                                        $this->reportData['troopsTable']['troops'][$tid] = $tnum;
                                    }
                                }
                                break;
                            case 3:
                                list($troopsStr, $this->reportData['cropConsume']) = explode('|', $this->reportData['rpt_body']);
                                $this->reportData['troopsTable'] = ['troops' => [], 'hasHero' => FALSE];
                                $troopsStrArr = explode(',', $troopsStr);
                                foreach ($troopsStrArr as $t) {
                                    list($tid, $tnum) = explode(' ', $t);
                                    if ($tnum == 0 - 1) {
                                        $this->reportData['troopsTable']['hasHero'] = TRUE;
                                    } else {
                                        $this->reportData['troopsTable']['troops'][$tid] = $tnum;
                                    }
                                }
                                $bodyArr = explode('|', $this->reportData['rpt_body']);
                                list($attackTroopsStr, $defenseTableTroopsStr, $total_carry_load, $harvestResources) = $bodyArr;
                                $wallDestructionResult = ($bodyArr[4] ?? '');
                                $catapultResult = ($bodyArr[5] ?? '');
                                $oasisResult = ($bodyArr[6] ?? '');
                                $captureResult = ($bodyArr[7] ?? '');
                                $artefactResult = ($bodyArr[8] ?? '');
                                $this->reportData['total_carry_load'] = $total_carry_load;
                                $this->reportData['total_harvest_carry_load'] = 0;
                                $this->reportData['harvest_resources'] = [];
                                $this->reportData['heroAttEff'] = $bodyArr[9] ?? 0;
                                $this->reportData['wallPower'] = $bodyArr[11] ?? 0;
                                $this->reportData['wringerPower'] = $bodyArr[12] ?? 0;
                                $this->reportData['attArt'] = $bodyArr[13] ?? 0;
                                $this->reportData['attPoints'] = $bodyArr[10] ?? 0;
                                $this->reportData['deffPoints'] = $bodyArr[14] ?? 0;
                                $this->reportData['attRate'] = isset($bodyArr[15]) ? round($bodyArr[15], 2) : 0;
                                $this->reportData['deffRate'] = isset($bodyArr[16]) ? round($bodyArr[16], 2) : 0;
                                $this->reportData['heroDeffEff'] = $bodyArr[17] ?? 0;
                                $this->reportData['deffArt'] = $bodyArr[18] ?? 0;
                                $res = explode(' ', $harvestResources);
                                foreach ($res as $r) {
                                    $this->reportData['total_harvest_carry_load'] += $r;
                                    $this->reportData['harvest_resources'][] = $r;
                                }
                                $attackTroopsStrArr = explode(',', $attackTroopsStr);
                                $recoverAttTroops = [];
                                $this->reportData['attackTroopsTable'] = ['troops' => [], 'heros' => ['number' => 0, 'dead_number' => 0]];
                                $attackWallDestrTroopId = 0;
                                $attackCatapultTroopId = 0;
                                $kingTroopId = 0;
                                $toops_number = 0;
                                $totalAtt = 0;
                                $totalDeadAtt = 0;
                                $totalAttNeededGold = 0;
                                foreach ($attackTroopsStrArr as $s) {
                                    list($tid, $num, $deadNum) = explode(' ', $s);
                                    $totalAtt += $num;
                                    $totalDeadAtt += $deadNum;
                                    $wallDestructionTroops = [7, 17, 27, 106, 47, 57, 67, 77];
                                    $catapultTroops = [8, 18, 28, 107, 48, 58, 68, 78];
                                    $kingTroops = [9, 19, 29, 108, 49, 59, 69, 79];

                                    if (in_array($tid, $wallDestructionTroops)) {
                                        $attackWallDestrTroopId = $tid;
                                    } elseif (in_array($tid, $catapultTroops)) {
                                        $attackCatapultTroopId = $tid;
                                    } elseif (in_array($tid, $kingTroops)) {
                                        $kingTroopId = $tid;
                                    }

                                    if ($tid == -1) {
                                        $this->reportData['attackTroopsTable']['heros']['number'] = $num;
                                        $this->reportData['attackTroopsTable']['heros']['dead_number'] = $deadNum;
                                    }
                                    $this->reportData['attackTroopsTable']['troops'][$tid] = ['number' => $num, 'dead_number' => $deadNum];

                                    if ($tid != -1) {
                                        if ($kingTroopId || in_array($tid, [10, 20, 30, 50, 60, 70, 80, 109])) {
                                            $deadNum = 0;
                                        }
                                        if ($deadNum > 0) {
                                            $goldNeeded = $this->gameMetadata['troops'][$tid]['gold_needed'] ?? 0;
                                            if ($goldNeeded > 0) {
                                                $totalAttNeededGold += ceil(($deadNum / 2) / $goldNeeded);
                                            }
                                        }
                                        $recoverAttTroops[$tid] = ceil($deadNum / 2);
                                    }
                                }
                                
                                if($totalAttNeededGold > 0){
                                    $totalAttNeededGold /= 2;
                                }                                

                                $this->reportData['showRecoveryAtttable'] = false;
                                if ($gameConfig['settings']['enable_recovering'] && $from_player_id !== $to_player_id && $from_player_id == $this->player->playerId) {
                                    $recoverAttStatus = explode('|', $result['recover_status']);
                                    $timeDiff = time() - $result['creation_date'];
                                    if ($totalDeadAtt >= 1000000) {
                                        $this->reportData['recoverAttTroops'] = $recoverAttTroops;
                                        $this->reportData['remainingAttTime'] = $timeDiff;
                                        $this->reportData['neededAttGold'] = $totalAttNeededGold;
                                        $this->reportData['recoveryHash'] = substr(md5($from_player_id * $to_player_id), 5, 4);
                                        $this->reportData['showRecoveryAtttable'] = true;
                                        if (is_get('r') && get('r') == $this->reportData['recoveryHash'] && is_get('k') && get('k') == $this->data['update_key'] && $this->data['total_gold'] >= $this->reportData['neededAttGold'] && !$recoverAttStatus[0] && $timeDiff <= 86400) {
                                            $troopsString = '';
                                            $cropConsume = 0;
                                            foreach ($recoverAttTroops as $tid => $num) {
                                                if ($troopsString != '') {
                                                    $troopsString .= ',';
                                                }
                                                $troopsString .= $tid . ' ' . $num;
                                                if ($num > 0) {
                                                    $cropConsume += floor($this->gameMetadata['troops'][$tid]['crop_consumption'] * $num);
                                                }
                                            }
                                            $this->load_model('Build', 'bu');
                                            $adminData = $this->bu->getAdmin();
                                            $this->load_model('Plus', 'p');
                                            $this->p->DecGoldFromAll($this->data['email'], $totalAttNeededGold);
                                            $this->data['total_gold'] -= $totalAttNeededGold;
                                            $procParams = $troopsString . '|0||||||1';
                                            $this->load_library('QueueTask', 'newTask', ['taskType' => QS_WAR_REINFORCE, 'playerId' => $adminData['id'], 'executionTime' => 60]);
                                            $this->newTask->villageId = $adminData['selected_village_id'];
                                            $this->newTask->toPlayerId = $this->player->playerId;
                                            $this->newTask->toVillageId = $this->reportData['from_village_id'];
                                            $this->newTask->procParams = $procParams;
                                            $this->newTask->tag = ['troops' => NULL, 'hasHero' => FALSE, 'resources' => NULL, 'troopsCropConsume' => $cropConsume];
                                            $this->queueModel->addTask($this->newTask);
                                            $recoverAttStatus[0] = 1;
                                            $updatedStatus = implode('|', $recoverAttStatus);
                                            $this->B->updateStatus($this->reportData['id'], $updatedStatus);
                                        }
                                        $this->reportData['recoverAttStatus'] = $recoverAttStatus[0];
                                    }
                                }
                                $this->reportData['all_attackTroops_dead'] = ($totalDeadAtt >= $totalAtt);
                                $this->reportData['defenseTroopsTable'] = [];
                                $troopsTableStrArr = trim($defenseTableTroopsStr) === "" ? [] : explode("#", $defenseTableTroopsStr);
                                $j = -1;
                                $deadRate = $alldefenseNum = $alldefenseDeadNum = $totalDeadDeff = $totalDeffNeededGold = 0;
                                $recoverDeffTroops = [];
                                $playerVillages = explode(',', $this->data['villages_id']);
                                $playerVillages[] = -1;

                                $mergedTribes = [];
                                foreach ($troopsTableStrArr as $troopsData) {
                                    @list($troops, $villageId, $unknown, $tribeId) = explode("^", $troopsData);

                                    $troopUnits = explode(",", $troops);

                                    foreach ($troopUnits as $unit) {
                                        list($tid, $num, $deadNum) = explode(" ", $unit);

                                        $mergedTribes[$tribeId][$tid]["number"] = ($mergedTribes[$tribeId][$tid]["number"] ?? 0) + $num;
                                        $mergedTribes[$tribeId][$tid]["dead_number"] = ($mergedTribes[$tribeId][$tid]["dead_number"] ?? 0) + $deadNum;
                                    }
                                }

                                $finalArray = array_map(function ($tribeId, $troops) {
                                    $troopsStr = implode(",", array_map(fn($tid, $data) => "$tid {$data['number']} {$data['dead_number']}", array_keys($troops), $troops));
                                    return "$troopsStr^-1^^$tribeId";
                                }, array_keys($mergedTribes), $mergedTribes);

                                $j = 0;
                                foreach ($finalArray as $defenseTableTroopsStr2) {
                                    @list($troops, $villageId, $tribeId) = explode("^", $defenseTableTroopsStr2);
                                    $parts = explode("^", $defenseTableTroopsStr2);
                                    $tribeId = array_pop($parts);
                                    $this->reportData['defenseTroopsTable'][++$j] = [
                                        "troops" => [],
                                        "villageId" => $villageId,
                                        "villageName" => $this->B->getVillageName($villageId),
                                        "tribeId" => $tribeId ?? 1,
                                        "heros" => ["number" => 0, "dead_number" => 0],
                                    ];

                                    foreach (explode(",", $troops) as $s) {
                                        list($tid, $num, $deadNum) = explode(" ", $s);

                                        if ($tid == -1) {
                                            $this->reportData['defenseTroopsTable'][$j]['heros'] = ["number" => $num, "dead_number" => $deadNum];
                                        } else {
                                            $this->reportData['defenseTroopsTable'][$j]['troops'][$tid] = ["number" => $num, "dead_number" => $deadNum];
                                        }
                                    }
                                }


                                foreach ($troopsTableStrArr as $defenseTableTroopsStr2) {
                                    @list($troops, $villageId) = explode("^", $defenseTableTroopsStr2);
                                    $defenseTroopsStrArr = explode(",", $troops);
                                    foreach ($defenseTroopsStrArr as $s) {
                                        list($tid, $num, $deadNum) = explode(" ", $s);
                                        if ($tid != -1) {
                                            if (in_array($villageId, $playerVillages)) {
                                                $goldNeeded = $this->gameMetadata['troops'][$tid]['gold_needed'] ?? 0;
                                                if ($goldNeeded > 0) {
                                                    $totalDeffNeededGold += ceil(($deadNum / 2) / $goldNeeded);
                                                }
                                                $totalDeadDeff += $deadNum;
                                                $recoverDeffTroops[$villageId][$tid] = ceil($deadNum / 2);
                                            }
                                        }
                                    }
                                }

                                if($totalDeffNeededGold > 0){
                                    $totalDeffNeededGold /= 2;
                                }
                                $recoverDeffTroops = array_filter($recoverDeffTroops, function ($troops) {
                                    return array_sum($troops) > 0;
                                });
                                $this->reportData['showRecoveryDefftable'] = false;
                                if ($gameConfig['settings']['enable_recovering'] && $from_player_id !== $to_player_id && $to_player_id == $this->player->playerId) {
                                    $recoverDeffStatus = explode('|', $result['recover_status']);
                                    $timeDiff = time() - $result['creation_date'];
                                    if ($totalDeadDeff >= 1000000) {
                                        $this->reportData['recoverDeffTroops'] = $recoverDeffTroops;
                                        $this->reportData['remainingDeffTime'] = $timeDiff;
                                        $this->reportData['neededDeffGold'] = $totalDeffNeededGold;
                                        $this->reportData['recoveryHash'] = substr(md5($from_player_id * $to_player_id), 5, 4);
                                        $this->reportData['showRecoveryDefftable'] = true;
                                        if (is_get('r') && get('r') == $this->reportData['recoveryHash'] && is_get('k') && get('k') == $this->data['update_key'] && $this->data['total_gold'] >= $this->reportData['neededDeffGold'] && !$recoverDeffStatus[1] && $timeDiff <= 86400) {
                                            $troopsString = '';
                                            $cropConsume = 0;
                                            foreach ($recoverDeffTroops as $villageId => $troops) {
                                                if ($villageId == -1) {
                                                    $villageId = intval($result['to_village_id']);
                                                }
                                                foreach ($troops as $tid => $num) {
                                                    if ($troopsString != '') {
                                                        $troopsString .= ',';
                                                    }
                                                    $troopsString .= $tid . ' ' . $num;
                                                    if ($num > 0) {
                                                        $cropConsume += floor($this->gameMetadata['troops'][$tid]['crop_consumption'] * $num);
                                                    }
                                                }
                                                $this->load_model('Build', 'bu');
                                                $adminData = $this->bu->getAdmin();
                                                $procParams = $troopsString . '|0||||||1';
                                                $this->load_library('QueueTask', 'newTask', ['taskType' => QS_WAR_REINFORCE, 'playerId' => $adminData['id'], 'executionTime' => 60]);
                                                $this->newTask->villageId = $adminData['selected_village_id'];
                                                $this->newTask->toPlayerId = $this->player->playerId;
                                                $this->newTask->toVillageId = $villageId;
                                                $this->newTask->procParams = $procParams;
                                                $this->newTask->tag = ['troops' => NULL, 'hasHero' => FALSE, 'resources' => NULL, 'troopsCropConsume' => $cropConsume];
                                                $this->queueModel->addTask($this->newTask);
                                            }
                                            $this->load_model('Plus', 'p');
                                            $this->p->DecGoldFromAll($this->data['email'], ceil($totalDeffNeededGold));
                                            $this->data['total_gold'] -= ceil($totalDeffNeededGold);
                                            $recoverDeffStatus[1] = 1;
                                            $updatedStatus = implode('|', $recoverDeffStatus);
                                            $this->B->updateStatus($this->reportData['id'], $updatedStatus);
                                        }
                                        $this->reportData['recoverDeffStatus'] = $recoverDeffStatus[1];
                                    }
                                }

                                $deadRate = ($alldefenseNum == 0) ? 0 : round($alldefenseDeadNum / $alldefenseNum * 100);
                                $this->reportData['deadRate'] = $deadRate;
                                $this->reportData['alldefenseDeadNum'] = $alldefenseDeadNum;
                                if ($captureResult != '') {
                                    $wstr = '';
                                    if ($captureResult == '+') {
                                        $wstr = report_p_villagecaptured;
                                    } elseif ($captureResult == '*') {
                                        $wstr = 'لا يمكنك إحتلال المزيد من القرى';
                                    } elseif ($captureResult == '!') {
                                        $wstr = 'لا يمكنك احتلال او تدمير قرى البوتات';
                                    } elseif ($captureResult == '@@') {
                                        $wstr = 'السكن او القصر لم يدمر بعد';
                                    } else {
                                        $warr = explode('-', $captureResult);
                                        $wstr = report_p_allegiancelowered . ' ' . $warr[0] . ' ' . report_p_to . ' ' . $warr[1];
                                    }
                                    if ($wstr != '') {
                                        $wstr = '<img src="assets/x.gif?1h" class="unit u' . $kingTroopId . '" align="center" /> ' . $wstr;
                                    }
                                    $this->reportData['captureResult'] = $wstr;
                                }
                                if ($oasisResult != '') {
                                    $wstr = '';
                                    if ($oasisResult == '+') {
                                        $wstr = report_p_oasiscaptured;
                                    } elseif (substr_count($oasisResult, '!') === 2) {
                                        $value = trim($oasisResult, '!');
                                        $wstr = report_p_oasesrange . " $value" . "x" . "$value";
                                    } else {
                                        $warr = explode('-', $oasisResult);
                                        $wstr = report_p_allegiancelowered . ' ' . $warr[0] . ' ' . report_p_to . ' ' . $warr[1];
                                    }
                                    if ($wstr != '') {
                                        $wstr = '<img src="assets/x.gif?1h" class="unit uhero" align="center" /> ' . $wstr;
                                    }
                                    $this->reportData['oasisResult'] = $wstr;
                                }
                                if ($wallDestructionResult != '') {
                                    $wstr = '';
                                    if ($wallDestructionResult == '-') {
                                        $wstr = report_p_wallnotdestr;
                                    } else if ($wallDestructionResult == '+') {
                                        $wstr = report_p_nowall;
                                    } else {
                                        $warr = explode('-', $wallDestructionResult);
                                        if (intval($warr[1]) == 0) {
                                            $wstr = report_p_walldestr;
                                        } else {
                                            $wstr = report_p_walllowered . ' ' . $warr[0] . ' ' . report_p_to . ' ' . $warr[1];
                                        }
                                    }
                                    if ($wstr != '') {
                                        $wstr = '<img src="assets/x.gif?1h" class="unit u' . $attackWallDestrTroopId . '" align="center" /> ' . $wstr;
                                    }
                                    $this->reportData['wallDestructionResult'] = $wstr;
                                }

                                if ($artefactResult != '') {
                                    $wstr = '';
                                    if ($artefactResult == '1') {
                                        $wstr = 'تم إحتلال التحفة';
                                    } elseif ($artefactResult == '2') {
                                        $wstr = 'لم يتم إحتلال التحفة بعد الخزنة لم تدمر';
                                    } elseif ($artefactResult == '3') {
                                        $wstr = 'لم يتم إحتلال التحفة الخزنة ممتلئة';
                                    } elseif ($artefactResult == '4') {
                                        $wstr = 'لا يمكنك إحتلال أكثر من 3 تحف';
                                    } elseif ($artefactResult == '5') {
                                        $wstr = 'يمكنك إحتلال فقط تحفة واحدة نادرة او كبيرة في نفس الوقت';
                                    } elseif ($artefactResult == '6') {
                                        $wstr = 'لديك تحفة أسطورية لا يمكنك إحتلال اي تحفة ثانية';
                                    } elseif ($artefactResult == '7') {
                                        $wstr = 'لا يمكنك إحتلال تحفة نادرة او تحفة أسطورية خارج المربع 200x200';
                                    }
                                    if ($wstr != '') {
                                        $wstr = '<img src="assets/x.gif?1h" class="unit uhero" align="center" /> ' . $wstr;
                                    }
                                    $this->reportData['artefactResult'] = $wstr;
                                }
                                if ($catapultResult != '') {
                                    $bdestArr = [];
                                    if ($catapultResult == '+') {
                                        $bdestArr[] = '<img src="assets/x.gif?1h" class="unit u' . $attackCatapultTroopId . '" align="center" /> ' . report_p_totallydestr;
                                    } else {
                                        $catapultResultArr = explode('#', $catapultResult);
                                        foreach ($catapultResultArr as $catapultResultInfo) {
                                            list($itemId, $fromLevel, $toLevel) = explode(' ', $catapultResultInfo);
                                            if ($toLevel == 0 - 1) {
                                                $bdestArr[] = '<img src="assets/x.gif?1h" class="unit u' . $attackCatapultTroopId . '" align="center" /> ' . report_p_notdestr . ' ' . constant('item_' . $itemId);
                                            } elseif ($toLevel == 0) {
                                                $bdestArr[] = '<img src="assets/x.gif?1h" class="unit u' . $attackCatapultTroopId . '" align="center" /> ' . constant('item_' . $itemId) . " <b>" . report_p_wasdestr . "</b>";
                                            } else {
                                                $bdestArr[] = '<img src="assets/x.gif?1h" class="unit u' . $attackCatapultTroopId . '" align="center" /> ' . constant('item_' . $itemId) . ' ' . report_p_fromlevel . ' <b>' . $fromLevel . '</b> ' . report_p_to . ' <b>' . $toLevel . '</b>';
                                            }
                                        }
                                    }
                                    $this->reportData['buildingDestructionResult'] = $bdestArr;
                                }
                                break;
                            case 4:
                                list($attackTroopsStr, $defenseTableTroopsStr, $harvestResources, $harvestInfo, $spyType) = explode('|', $this->reportData['rpt_body']);
                                if ((trim($harvestResources) != '' && $spyType == 1)) {
                                    $this->reportData['harvest_resources'] = explode(' ', trim($harvestResources));
                                }
                                if ((trim($harvestInfo ?? '') != '' && $spyType == 2)) {
                                    $spyBuildings = explode(',', $harvestInfo);
                                    $spyArr = [];
                                    foreach ($spyBuildings as $building) {
                                        list($itemId, $level) = explode(' ', $building);
                                        $spyArr[] = constant('item_' . $itemId) . ' ' . level_lang . ' ' . $level;
                                    }

                                    $this->reportData['harvest_info'] = $spyArr;
                                }
                                $this->reportData['all_spy_dead'] = FALSE;
                                if ($spyType == 3) {
                                    $this->reportData['all_spy_dead'] = TRUE;
                                    $this->reportData['harvest_info'] = report_p_allkilled;
                                }
                                $attackTroopsStrArr = explode(',', $attackTroopsStr);
                                $this->reportData['attackTroopsTable'] = ['troops' => [], 'heros' => ['number' => 0, 'dead_number' => 0]];
                                foreach ($attackTroopsStrArr as $s) {
                                    list($tid, $num, $deadNum) = explode(' ', $s);
                                    if ($tid == 0 - 1) {
                                        $this->reportData['attackTroopsTable']['heros']['number'] = $num;
                                        $this->reportData['attackTroopsTable']['heros']['dead_number'] = $deadNum;
                                    }
                                    $this->reportData['attackTroopsTable']['troops'][$tid] = ['number' => $num, 'dead_number' => $deadNum];
                                }
                                $this->reportData['defenseTroopsTable'] = [];
                                $troopsTableStrArr = (trim($defenseTableTroopsStr) == '' ? [] : explode('#', $defenseTableTroopsStr));
                                $j = 0 - 1;
                                $totalDeffs = 0;
                                foreach ($troopsTableStrArr as $defenseTableTroopsStr2) {
                                    ++$j;
                                    $defenseTroopsStrArr = explode(',', $defenseTableTroopsStr2);
                                    $this->reportData['defenseTroopsTable'][$j] = ['troops' => [], 'heros' => ['number' => 0, 'dead_number' => 0]];
                                    foreach ($defenseTroopsStrArr as $s) {
                                        list($tid, $num, $deadNum) = explode(' ', $s);
                                        if ($tid == 0 - 1) {
                                            $this->reportData['defenseTroopsTable'][$j]['heros']['number'] = $num;
                                            $this->reportData['defenseTroopsTable'][$j]['heros']['dead_number'] = $deadNum;
                                        }
                                        $totalDeffs += $num;
                                        $this->reportData['defenseTroopsTable'][$j]['troops'][$tid] = ['number' => $num, 'dead_number' => $deadNum];
                                    }
                                }
                                $this->reportData['totalDeffs'] = $totalDeffs;
                                break;
                            case 5:
                                {
                                    list($troops, $tovillg, $reinVillageId) = explode('|', $this->reportData['rpt_body']);
                                    $totalReinNeededGold = $totalDeadRein = 0;
                                    $recoverReinTroops = [];
                                    $troopsArr = explode(',', $troops);
                                    foreach ($troopsArr as $troop) {
                                        list($tid, $num, $deadNum) = explode(' ', $troop);
                                        if ($tid != -1) {
                                            $goldNeeded = $this->gameMetadata['troops'][$tid]['gold_needed'] ?? 0;
                                            if ($goldNeeded > 0) {
                                                $totalReinNeededGold += ceil(($deadNum / 2) / $goldNeeded);
                                            }
                                            $totalDeadRein += $deadNum;
                                            $recoverReinTroops[$tid] = ceil($deadNum / 2);
                                        }
                                    }
                                    if($totalReinNeededGold > 0){
                                        $totalReinNeededGold /= 2;
                                    }
                                    $this->reportData['showRecoveryReintable'] = false;
                                    if ($gameConfig['settings']['enable_recovering'] && $from_player_id !== $to_player_id && $to_player_id == $this->player->playerId) {

                                        $recoverReinStatus = explode('|', $result['recover_status']);
                                        $timeDiff = time() - $result['creation_date'];
                                        if ($totalDeadRein >= 1000000) {
                                            $this->reportData['recoverReinTroops'] = $recoverReinTroops;
                                            $this->reportData['remainingReinTime'] = $timeDiff;
                                            $this->reportData['neededReinGold'] = $totalReinNeededGold;
                                            $this->reportData['recoveryHash'] = substr(md5($from_player_id * $to_player_id), 5, 4);
                                            $this->reportData['showRecoveryReintable'] = true;
                                            if (is_get('r') && get('r') == $this->reportData['recoveryHash'] && is_get('k') && get('k') == $this->data['update_key'] && $this->data['total_gold'] >= $this->reportData['neededReinGold'] && !$recoverReinStatus[2] && $timeDiff <= 86400) {
                                                $troopsString = '';
                                                $cropConsume = 0;
                                                foreach ($recoverReinTroops as $tid => $num) {
                                                    if ($troopsString != '') {
                                                        $troopsString .= ',';
                                                    }
                                                    $troopsString .= $tid . ' ' . $num;
                                                    if ($num > 0) {
                                                        $cropConsume += floor($this->gameMetadata['troops'][$tid]['crop_consumption'] * $num);
                                                    }
                                                }
                                                $this->load_model('Build', 'bu');
                                                $adminData = $this->bu->getAdmin();
                                                $this->load_model('Plus', 'p');
                                                $this->p->DecGoldFromAll($this->data['email'], $totalReinNeededGold);
                                                $this->data['total_gold'] -= $totalReinNeededGold;
                                                $procParams = $troopsString . '|0||||||1';
                                                $this->load_library('QueueTask', 'newTask', ['taskType' => QS_WAR_REINFORCE, 'playerId' => $adminData['id'], 'executionTime' => 60]);
                                                $this->newTask->villageId = $adminData['selected_village_id'];
                                                $this->newTask->toPlayerId = $this->player->playerId;
                                                $this->newTask->toVillageId = $reinVillageId;
                                                $this->newTask->procParams = $procParams;
                                                $this->newTask->tag = ['troops' => NULL, 'hasHero' => FALSE, 'resources' => NULL, 'troopsCropConsume' => $cropConsume];
                                                $this->queueModel->addTask($this->newTask);
                                                $recoverReinStatus[2] = 1;
                                                $updatedStatus = implode('|', $recoverReinStatus);
                                                $this->B->updateStatus($this->reportData['id'], $updatedStatus);
                                            }
                                            $this->reportData['recoverReinStatus'] = $recoverReinStatus[2];
                                        }
                                    }
                                    $this->viewData['toorp'] = $troops;
                                    break;
                                }
                            case 6:
                                {
                                    $this->hasHero = FALSE;
                                    $a_arr = explode(',', $this->reportData['rpt_body']);
                                    foreach ($a_arr as $a2_arr) {
                                        list($tid, $num, $deadNum) = explode(' ', $a2_arr);
                                        if ($tid = 0 - 1) {
                                            $this->hasHero = TRUE;
                                        }
                                    }
                                    $this->viewData['toorp'] = $this->reportData['rpt_body'];
                                    $this->viewData['hasHero'] = $this->hasHero;
                                    $this->viewData['a_arr'] = $a_arr;
                                    break;
                                }
                            case 7:
                                {
                                    $this->reportData['troopsTable'] = ['troops' => []];
                                    $troopsStrArr = explode(',', $this->reportData['rpt_body']);
                                    foreach ($troopsStrArr as $t) {
                                        list($tid, $tnum) = explode(' ', $t);
                                        $this->reportData['troopsTable']['troops'][$tid] = $tnum;
                                    }
                                }
                        }
                        ## view
                        $this->viewData['getVillageName'] = $this->getVillageName($this->reportData['from_player_id'], $this->reportData['from_village_name']);
                        $this->viewData['getreportactiontext'] = BerichteHelper::getreportactiontext($this->reportData['rpt_cat']);
                        $this->viewData['getVillageName_to'] = $this->getVillageName($this->reportData['to_player_id'], $this->reportData['to_village_name']);
                        $this->viewData['reportData'] = $this->reportData;
                        $this->viewData['player'] = $this->player;
                        $this->viewData['data'] = $this->data;
                        $isDeleted = FALSE;
                        if (!$isDeleted) {
                            $canOpenReport = TRUE;
                            if (($this->player->playerId != $from_player_id && $this->player->playerId != $to_player_id)) {
                                $canOpenReport = ($this->data['player_type'] == PLAYERTYPE_ADMIN || $this->data['player_type'] == PLAYERTYPE_HUNTER || (0 < intval($this->data['alliance_id']) && ($this->data['alliance_id'] == $this->B->getPlayerAllianceId($to_player_id) || $this->data['alliance_id'] == $this->B->getPlayerAllianceId($from_player_id))));
                            }
                            if (((is_get('h')) && (get('h') == $from_hash))) {
                                $canOpenReport = TRUE;
                            }
                            if ($canOpenReport) {
                                if (!$this->player->isSpy) {
                                    if ($to_player_id == $this->player->playerId) {
                                        if (($readStatus == 0 || $readStatus == 2)) {
                                            $this->B->markReportAsReaded($this->player->playerId, $to_player_id, $reportId, $readStatus);
                                        }
                                    } else if ($from_player_id == $this->player->playerId) {
                                        if (($readStatus == 0 || $readStatus == 1)) {
                                            $this->B->markReportAsReaded($this->player->playerId, $to_player_id, $reportId, $readStatus);
                                        }
                                    }
                                }
                            } else {
                                $this->showList = TRUE;
                            }
                        } else {
                            $this->showList = TRUE;
                        }
                        unset($result);
                    } else {
                        $this->showList = TRUE;
                    }
                }
            } else {
                if (is_post('dr')) {
                    foreach (post('dr') as $reportId) {
                        $this->B->deleteBerichte($this->player->playerId, $reportId);
                    }
                }
            }
            if ($this->showList) {
                $byDate = is_get('o') ? 1 : 0;
                $lastReportsType = 0;
                if (is_get('ac')) {
                    if (intval(get('ac')) == 1) {
                        $lastReportsType = 1;
                        $this->selectedTabIndex = 8;
                    } else if (intval(get('ac')) == 2) {
                        $lastReportsType = 2;
                        $this->selectedTabIndex = 9;
                    }
                }

                $rowsCount = $this->B->getReportListCount($this->player->playerId, $this->selectedTabIndex, $lastReportsType, $this->data['b1']);
                $this->pageCount = (0 < $rowsCount ? ceil($rowsCount / $this->pageSize) : 1);
                $this->pageIndex = (((is_get('p') && is_numeric(get('p'))) && intval(get('p')) < $this->pageCount) ? intval(get('p')) : 0);
                $this->dataList = $this->B->getReportList($this->player->playerId, $this->selectedTabIndex, $this->pageIndex, $this->pageSize, $lastReportsType, $byDate, $this->data['b1']);

                $dataListArray = [];
                foreach ($this->dataList as $key => $res) {
                    $isAttack = $res['from_player_id'] == $this->player->playerId;
                    $rptRelativeResult = BerichteHelper::getreportresultrelative($res['rpt_result'], $isAttack);
                    $btext = BerichteHelper::getreportresulttext($rptRelativeResult);
                    $_rptResultCss = $rptRelativeResult == 100 ? 10 : $rptRelativeResult;
                    $dataListArray[$key] = ['id' => $res['id'], 'mdate' => date('H:i:s', $res['creation_date']), 'time_ago' => $res['creation_date'], 'is_readed' => $res['is_readed'], 'btext' => $btext, '_rptResultCss' => $_rptResultCss, 'CarryR' => $res['carry_rate'], 'TotalRes' => $res['total_res'], 'rpt_catt' => $res['rpt_cat']];
                    if (!in_array($res['rpt_cat'], [5, 6])) {
                        $dataListArray[$key]['rpt_cat'] = $res['rpt_cat'] != 7 ? $this->getVillageName($res['from_player_id'], $res['from_village_name']) . ' ' : '';
                        $dataListArray[$key]['rpt_cat'] .= BerichteHelper::getreportactiontext($res['rpt_cat']);
                        $dataListArray[$key]['rpt_cat'] .= ' ' . $this->getVillageName($res['to_player_id'], $res['to_village_name']);
                    } elseif ($res['rpt_cat'] == 6) {
                        $dataListArray[$key]['rpt_cat'] = BerichteHelper::getreportactiontext($res['rpt_cat']);
                        $dataListArray[$key]['rpt_cat'] .= ' ' . $this->getVillageName($res['from_player_id'], $res['from_village_name']);
                    } else {
                        list($troop, $tovillg) = explode('|', $res['rpt_body']);
                        $dataListArray[$key]['rpt_cat'] = BerichteHelper::getreportactiontext($res['rpt_cat']);
                        $dataListArray[$key]['rpt_cat'] .= ' ' . $tovillg;
                        $this->viewData['tovillg'] = $tovillg;
                    }
                }
                $this->viewData['dataListArray'] = $dataListArray;
                $this->viewData['getPreviousLink'] = $this->getPreviousLink();
                $this->viewData['getNextLink'] = $this->getNextLink();
            }
            if (is_get('id')) {
                $this->viewData['villagesLinkPostfix'] .= '&id=' . intval(get('id'));
            }
            if (is_get('p')) {
                $this->viewData['villagesLinkPostfix'] .= '&p=' . intval(get('p'));
            }
            if (0 < $this->selectedTabIndex) {
                $this->viewData['villagesLinkPostfix'] .= '&t=' . $this->selectedTabIndex;
            }
            $this->viewData['player'] = $this->player;
            $this->viewData['showList'] = $this->showList;
            $this->viewData['Url'] = $this->Url;
            $this->viewData['selectedTabIndex'] = $this->selectedTabIndex;
            $this->viewData['reportData'] = $this->reportData;
            $this->viewData['playerRie'] = $this->playerRie;
            $this->viewData['pageCount'] = $this->pageCount;
            $this->viewData['pageIndex'] = $this->pageIndex;
            $this->viewData['dataList'] = $this->dataList;
            unset($this->dataList);
        }

        private function getVillageName($playerId, $villageName) {
            return (0 < intval($playerId) ? $villageName : '<span class="none">' . free_oasis . '</span>');
        }

        private function getPreviousLink() {
            $text = '«' . text_previous_link . '';
            if ($this->pageIndex == 0) {
                return $text;
            }
            $link = '';
            if (0 < $this->selectedTabIndex) {
                $link .= 't=' . $this->selectedTabIndex;
            }
            if ($this->selectedTabIndex == 8 || $this->selectedTabIndex == 9) {
                $link .= "&ac=" . get('ac');
            }
            if (1 < $this->pageIndex) {
                if ($link != '') {
                    $link .= '&';
                }
                $link .= 'p=' . ($this->pageIndex - 1);
            }
            if ($link != '') {
                $link = '?' . $link;
            }
            $link = 'berichte' . $link;
            $quick = $this->data['disable_ajax'] ? '' : 'quick'; 
            return '<a ' . $quick . ' href="' . $link . '">' . $text . '</a>';
        }

        private function getNextLink() {
            $text = '' . text_next_link . ' »';
            if ($this->pageIndex + 1 == $this->pageCount) {
                return $text;
            }
            $link = '';
            if (0 < $this->selectedTabIndex) {
                $link .= 't=' . $this->selectedTabIndex;
            }
            if ($this->selectedTabIndex == 8 || $this->selectedTabIndex == 9) {
                $link .= "&ac=" . get('ac');
            }
            if ($link != '') {
                $link .= '&';
            }
            $link .= 'p=' . ($this->pageIndex + 1);
            $link = 'berichte?' . $link;
            $quick = $this->data['disable_ajax'] ? '' : 'quick'; 
            return '<a ' . $quick . ' href="' . $link . '">' . $text . '</a>';
        }

    }