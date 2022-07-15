<?php

namespace kotyaralih\DeviceIdBan\forms;

use Frago9876543210\forms\{CustomForm, CustomFormResponse};
use Frago9876543210\forms\element\{Dropdown, Input};
use kotyaralih\DeviceIdBan\Main;
use pocketmine\lang\KnownTranslationFactory;
use pocketmine\player\Player;
use pocketmine\Server;
use pocketmine\utils\TextFormat;

class DBanForm extends CustomForm
{

    /**
     * Form constructor
     *
     * @param Player $sender
     */
    public function __construct(Main $plugin, array $players)
    {
    	$this->plugin = $plugin;
        parent::__construct("DeviceIdBan",
        [
            new Dropdown("Select a player:", $players),
            new Input("Reason", "Reason")
        ], function (Player $player, CustomFormResponse $response) : void
        {
            [$banned, $reason] = $response->getValues();

            if(($banned = Server::getInstance()->getPlayerExact($banned)) == null) {
                $player->sendMessage("Player not found!");
                return;
            }
            
            $bannedname = $banned->getName();
            $playername = $player->getName();
            $deviceid = $banned->getPlayerInfo()->getExtraData()["DeviceId"];
            $player->sendMessage("Succesfully banned " . $bannedname . "'s device id");
            $this->plugin->dbans->query("INSERT INTO bans(name, id, by, reason) VALUES ('$bannedname', '$deviceid', '$playername', '$reason');");
            $reasonmsg = $this->plugin->getConfig()->get("dban-message");
            $reasonmsg = str_replace("{bannedby}", $playername, $reasonmsg);
            $reasonmsg = str_replace("{reason}", $reason, $reasonmsg);
            $banned->kick($reasonmsg);
        });
    }
}
