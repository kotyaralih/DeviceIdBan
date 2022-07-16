<?php

namespace kotyaralih\DeviceIdBan;

use pocketmine\plugin\{Plugin, PluginBase};
use pocketmine\player\Player;
use pocketmine\event\Listener;
use pocketmine\event\player\PlayerPreLoginEvent;
use pocketmine\command\{Command, CommandSender};

class Main extends PluginBase implements Listener{
        
        public $dbans;
        
	public function onEnable() : void{
		$this->getServer()->getPluginManager()->registerEvents($this, $this);
		$this->saveDefaultConfig();
		$this->dbans = new \SQLite3("plugin_data/DeviceIdBan/devicebans.db");
		$this->dbans->query("CREATE TABLE IF NOT EXISTS bans(name TEXT NOT NULL, id TEXT NOT NULL, by TEXT NOT NULL, reason TEXT NOT NULL);");
	}
	
	public function onPreLogin(PlayerPreLoginEvent $event){
		$deviceid = $event->getPlayerInfo()->getExtraData()["DeviceId"];
		$dban = $this->dbans->query("SELECT * FROM bans WHERE id = '$deviceid'")->fetchArray(SQLITE3_ASSOC);
		if($dban){
			$reason = $this->getConfig()->get("dban-message");
			$reason = str_replace("{bannedby}", $dban["by"], $reason);
			$reason = str_replace("{reason}", $dban["reason"], $reason);
			$event->setKickReason(0, $reason);
		}
	}
	
	public function onCommand(CommandSender $sender, Command $cmd, string $label, array $args) :bool { // should be fixed
		if(strtolower($cmd->getName()) == "deviceban"){
			if(count($args) < 2){
				if(!$sender instanceof Player){
					$sender->sendMessage("Usage: /deviceban <player> <reason>");
					return true;
				}
				$players = [];
				foreach($this->getServer()->getOnlinePlayers() as $player){
					if($sender->getName() !== $player->getName()){
						$players[] = $player->getName();
					}
				}
				if($players == []){
					$sender->sendMessage("No players online");
					return true;
				}
				$sender->sendForm(new forms\DBanForm($this, $players));
				return true;
			}
			if($this->getServer()->getPlayerExact($args[0]) == null){
				$sender->sendMessage("Player " . $args[0] . " not found!");
				return true;
			}
			if($sender == $this->getServer()->getPlayerExact($args[0])){
				$sender->sendMessage("You can't ban yourself!");
				return true;
			}
			$banned = $this->getServer()->getPlayerExact($args[0]);
			$bannedname = $banned->getName();
			$sendername = $sender->getName();
			$deviceid = $banned->getPlayerInfo()->getExtraData()["DeviceId"];
			$sender->sendMessage("Succesfully banned " . $bannedname . "'s device id");
			unset($args[0]);
			$reason = trim(implode(" ", $args));
			$this->dbans->query("INSERT INTO bans(name, id, by, reason) VALUES ('$bannedname', '$deviceid', '$sendername', '$reason');");
			$reasonmsg = $this->getConfig()->get("dban-message");
			$reasonmsg = str_replace("{bannedby}", $sendername, $reasonmsg);
			$reasonmsg = str_replace("{reason}", $reason, $reasonmsg);
			$banned->kick($reasonmsg);
			return true;
		}
		if(strtolower($cmd->getName()) == "devicepardon"){
			if(count($args) < 1){
				if(!$sender instanceof Player){
					$sender->sendMessage("Usage: /devicepardon <player>");
					return true;
				}
				$sender->sendForm(new forms\DPardonForm($this));
				return;
			}
			$dban = $this->dbans->query("SELECT * FROM bans WHERE name = '$args[0]'")->fetchArray(SQLITE3_ASSOC);
			if(!$dban){
				$sender->sendMessage($args[0] . "'s device id is not banned!");
				return true;
			}
			$sender->sendMessage("Succesfully unbanned " . $args[0] . "'s device id");
			$this->dbans->query("DELETE FROM bans WHERE name = '$args[0]'");
			return true;
		}
	}
	
}
