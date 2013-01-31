<?php
#################################################################################
##                                                                             ##
##                                                                             ##
## --------------------------------------------------------------------------- ##
##                                                                             ##
##  Project:       TATAR WARS                                                  ##
##  Version:       2012.3.15                                                   ##
##  License:       Creative Commons BY-NC-SA 3.0                               ##
##  Copyright:     Bazaid (c) 2012 - All rights reserved                       ##
##  Source code:   https://github.com/Bazaid/tatar-wars                        ##
##                 http://sourceforge.net/projects/tatarwars/                  ##
#################################################################################


class RegisterModel extends ModelBase
{

    public function isPlayerNameExists( $playerName )
    {
        return 0 < $this->provider->fetchScalar( "SELECT COUNT(*) FROM p_players p WHERE p.name='%s'", array(
            $playerName
        ) );
    }

    public function isPlayerEmailExists( $playerEmail )
    {
        return 0 < $this->provider->fetchScalar( "SELECT COUNT(*) FROM p_players p WHERE p.email='%s'", array(
            $playerEmail
        ) );
    }

    public function _getEmptyVillageId( $position, $mapSize )
    {
        $halfMapSize = floor( $mapSize / 2 );
        $positionArray = array(
            0 - $halfMapSize,
            $halfMapSize,
            0 - $halfMapSize,
            $halfMapSize
        );
        switch ( $position )
        {
        case 1 :
            $positionArray = array(
                0 - $halfMapSize,
                0,
                0,
                $halfMapSize
            );
            break;
        case 2 :
            $positionArray = array(
                0,
                $halfMapSize,
                0,
                $halfMapSize
            );
            break;
        case 3 :
            $positionArray = array(
                0 - $halfMapSize,
                0,
                0 - $halfMapSize,
                0
            );
            break;
        case 4 :
            $positionArray = array(
                0,
                $halfMapSize,
                0 - $halfMapSize,
                0
            );
        }
        return $this->provider->fetchRow( "SELECT v.id, v.rel_x, v.rel_y FROM p_villages v\r\n\t\t\t\tWHERE \r\n\t\t\t\t\tv.field_maps_id=3\r\n\t\t\t\t\tAND ISNULL(v.player_id)\r\n\t\t\t\t\tAND (v.rel_x >= %s AND v.rel_x <= %s)\r\n\t\t\t\t\tAND (v.rel_y >= %s AND v.rel_y <= %s)\r\n\t\t\t\t\tAND v.rand_num > 0\r\n\t\t\t\tORDER BY v.rand_num\r\n\t\t\t\tLIMIT 1", $positionArray );
    }

    public function createVillage( $playerId, $tribeId, $villageId, $playerName, $villageName, $playerType )
    {
        $GameMetadata = $GLOBALS['GameMetadata'];
        $isSpecial = $playerType == PLAYERTYPE_TATAR;
        $row = $this->provider->fetchRow( "SELECT v.player_id,v.field_maps_id FROM p_villages v WHERE v.id=%s", array(
            $villageId
        ) );
        if ( 0 < intval( $row['player_id'] ) )
        {
            return FALSE;
        }
        $update_key = substr( md5( $playerId.$tribeId.$villageId.$playerName.$villageName ), 2, 5 );
        $field_map_id = $row['field_maps_id'];
        $buildings = "";
        foreach ( $GLOBALS['SetupMetadata']['field_maps'][$field_map_id] as $v )
        {
            if ( $buildings != "" )
            {
                $buildings .= ",";
            }
            $buildings .= sprintf( "%s 0 0", $v );
        }
        $k = 19;
        while ( $k <= 40 )
        {
            $buildings .= $k == 26 && !$isSpecial ? ",15 1 0" : ",0 0 0";
            ++$k;
        }
        $resources = "";
        $farr = explode( "-", $GLOBALS['SetupMetadata']['field_maps_summary'][$field_map_id] );
        $i = 1;
        $_c = sizeof( $farr );
        while ( $i <= $_c )
        {
            if ( $resources != "" )
            {
                $resources .= ",";
            }
            $resources .= sprintf( "%s 1300 1500 1500 %s 0", $i, $farr[$i - 1] * 2 * $GameMetadata['game_speed'] );
            ++$i;
        }
        $troops_training = "";
        $troops_num = "";
        foreach ( $GameMetadata['troops'] as $k => $v )
        {
            if ( $v['for_tribe_id'] == 0 - 1 || $v['for_tribe_id'] == $tribeId )
            {
                if ( $troops_training != "" )
                {
                    $troops_training .= ",";
                }
                $researching_done = $v['research_time_consume'] == 0 ? 1 : 0;
                $troops_training .= $k." ".$researching_done." 0 0";
                if ( $troops_num != "" )
                {
                    $troops_num .= ",";
                }
                $troops_num .= $k." 0";
            }
        }
        $troops_num = "-1:".$troops_num;
        $this->provider->executeQuery( "UPDATE p_villages v\r\n\t\t\tSET\r\n\t\t\t\tv.last_update_date=NOW(),\r\n\t\t\t\tv.tribe_id=%s,\r\n\t\t\t\tv.player_id=%s,\r\n\t\t\t\tv.player_name='%s',\r\n\t\t\t\tv.village_name='%s',\r\n\t\t\t\tv.is_capital=1,\r\n\t\t\t\tv.is_special_village=%s,\r\n\t\t\t\tv.creation_date=NOW(),\r\n\t\t\t\tv.buildings='%s',\r\n\t\t\t\tv.resources='%s',\r\n\t\t\t\tv.cp='0 2',\r\n\t\t\t\tv.troops_training='%s',\r\n\t\t\t\tv.troops_num='%s',\r\n\t\t\t\tv.update_key='%s'\r\n\t\t\tWHERE v.id=%s", array(
            $tribeId,
            $playerId,
            $playerName,
            $villageName,
            $isSpecial ? "1" : "0",
            $buildings,
            $resources,
            $troops_training,
            $troops_num,
            $update_key,
            $villageId
        ) );
        return TRUE;
    }

    public function createNewPlayer( $playerName, $playerEmail, $playerPassword, $tribeId, $position, $villageName, $mapSize, $playerType, $villageCount = 1, $snID = 0 )
    {
        $this->provider->executeQuery( "INSERT p_players \r\n\t\t\tSET\r\n\t\t\t\ttribe_id='%s',\r\n\t\t\t\tname='%s',\r\n\t\t\t\tpwd='%s',\r\n\t\t\t\temail='%s',\r\n\t\t\t\tis_active=%s,\r\n\t\t\t\tactive_plus_account=0,\r\n\t\t\t\tis_blocked=0,\r\n\t\t\t\tregistration_date=NOW(),\r\n\t\t\t\tplayer_type=%s,\r\n\t\t\t\tgold_num=0,\r\n\t\t\t\tsnid=%s,\r\n\t\t\t\tmedals='0::'", array(
            $tribeId,
            $playerName,
            md5( $playerPassword ),
            $playerEmail,
            $playerType == PLAYERTYPE_ADMIN ? 1 : 0,
            $playerType,
            intval( $snID )
        ) );
        $playerId = $this->provider->fetchScalar( "SELECT LAST_INSERT_ID() FROM p_players" );
        if ( !$playerId )
        {
            return array( "hasErrors" => TRUE );
        }
        $villages = array( );
        $i = 0;
        while ( $i < $villageCount )
        {
            $vrow = NULL;
            if ( $playerType == PLAYERTYPE_ADMIN )
            {
                $vrow = array( "id" => 1, "rel_x" => 0, "rel_y" => 0 );
            }
            else
            {
                $vrow = $this->_getEmptyVillageId( $position, $mapSize );
            }
            $villageId = $vrow['id'];
            $villages[$villageId] = array(
                $vrow['rel_x'],
                $vrow['rel_y'],
                $villageName
            );
            if ( !$villageId )
            {
                $this->provider->executeQuery( "DELETE FROM p_players WHERE id=%s", array(
                    $playerId
                ) );
                return array( "hasErrors" => TRUE );
            }
            $trialsCount = 1;
            while ( !$this->createVillage( $playerId, $tribeId, $villageId, $playerName, $villageName, $playerType ) )
            {
                unset( $villages[$villageId] );
                if ( $trialsCount == 3 )
                {
                    $this->provider->executeQuery( "DELETE FROM p_players WHERE id=%s", array(
                        $playerId
                    ) );
                    return array( "hasErrors" => TRUE );
                }
                ++$trialsCount;
                $vrow = $this->_getEmptyVillageId( $position, $mapSize );
                $villageId = $vrow['id'];
                $villages[$villageId] = array(
                    $vrow['rel_x'],
                    $vrow['rel_y'],
                    $villageName
                );
            }
            ++$i;
        }
        $villages_id = "";
        $villages_data = "";
        foreach ( $villages as $k => $v )
        {
            if ( $villages_id != "" )
            {
                $villages_id .= ",";
                $villages_data .= "\n";
            }
            $villages_data .= $k." ".implode( " ", $v );
            $villages_id .= $k;
        }
        $activationCode = substr( md5( dechex( $playerId ).dechex( $villageId ) ), 5, 10 );
        $this->provider->executeQuery( "UPDATE p_players\r\n\t\t\tSET\r\n\t\t\t\tactivation_code='%s',\r\n\t\t\t\tselected_village_id=%s,\r\n\t\t\t\tvillages_id='%s',\r\n\t\t\t\tvillages_data='%s',\r\n\t\t\t\tvillages_count=%s\r\n\t\t\tWHERE\r\n\t\t\t\tid=%s", array(
            $activationCode,
            $villageId,
            $villages_id,
            $villages_data,
            sizeof( $villages ),
            $playerId
        ) );
        $expr = "";
        switch ( $tribeId )
        {
        case 1 :
            $expr = ",gs.Roman_players_count=gs.Roman_players_count+1";
            break;
        case 2 :
            $expr = ",gs.Teutonic_players_count=gs.Teutonic_players_count+1";
            break;
        case 3 :
            $expr = ",gs.Gallic_players_count=gs.Gallic_players_count+1";
            break;
        case 6 :
            $expr = ",gs.Dboor_players_count=gs.Dboor_players_count+1";
            break;
        case 7 :
            $expr = ",gs.Arab_players_count=gs.Arab_players_count+1";
        }
        if ( $playerType == PLAYERTYPE_ADMIN )
        {
            $expr .= ",gs.active_players_count=gs.active_players_count+1";
        }
        if ( $expr != "" )
        {
            $this->provider->executeQuery( "UPDATE g_summary gs SET gs.players_count=gs.players_count+1".$expr );
        }
        return array(
            "playerId" => $playerId,
            "villages" => $villages,
            "activationCode" => $activationCode,
            "hasErrors" => FALSE
        );
    }

}

?>
