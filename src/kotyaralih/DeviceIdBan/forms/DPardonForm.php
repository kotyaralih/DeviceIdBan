<?php

namespace kotyaralih\DeviceIdBan\forms;

use Frago9876543210\forms\{CustomForm, CustomFormResponse};
use Frago9876543210\forms\element\{Dropdown, Input};
use kotyaralih\DeviceIdBan\Main;
use pocketmine\lang\KnownTranslationFactory;
use pocketmine\player\Player;
use pocketmine\Server;
use pocketmine\utils\TextFormat;

class DPardonForm extends CustomForm
{

    /**
     * Form constructor
     *
     * @param Player $player
     */
    public function __construct(Main $plugin)
    {
    	$this->plugin = $plugin;
        parent::__construct("DeviceIdPardon",
        [
            new Input("Nickname", "Nickname")
        ], function (Player $player, CustomFormResponse $response) : void
        {
            [$banned] = $response->getValues();

            $dban = $this->plugin->dbans->query("SELECT * FROM bans WHERE name = '$banned'")->fetchArray(SQLITE3_ASSOC);
            if(!$dban){
            	$player->sendMessage($banned . "'s device id is not banned!");
                return;
            }
            $player->sendMessage("Succesfully unbanned " . $banned . "'s device id");
			$this->plugin->dbans->query("DELETE FROM bans WHERE name = '$banned'");
			return;
        });
    }
}
