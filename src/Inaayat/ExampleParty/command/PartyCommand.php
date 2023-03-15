<?php

namespace Inaayat\ExampleParty\command;

use Inaayat\ExampleParty\Main;
use Inaayat\Party\PartyAPI;
use pocketmine\command\Command;
use pocketmine\command\CommandSender;
use pocketmine\permission\DefaultPermissions;
use pocketmine\player\Player;
use pocketmine\utils\TextFormat;

class PartyCommand extends Command {

    private $plugin;
  
    public function __construct(Main $plugin) {
        $this->plugin = $plugin;
        parent::__construct("party");
        $this->setDescription("Party Command");
        $this->setPermission(DefaultPermissions::ROOT_USER);
    }

    public function execute(CommandSender $sender, string $commandLabel, array $args) {
        if (!$sender instanceof Player) {
            $sender->sendMessage(TextFormat::RED . "This command can only be used by players");
            return true;
        }
        $partyManager = PartyAPI::getInstance();
        $party = $partyManager->getPlayerParty($sender);
        if (count($args) === 0) {
            if ($party !== null) {
                $sender->sendMessage(TextFormat::GREEN . "You are in a party led by " . $party->getLeader()->getName());
                $members = array_map(function ($player) {
                    return $player->getName();
                }, $party->getMembers());
                $sender->sendMessage(TextFormat:: GREEN . "Members: " . implode(", ", $members));
            } else {
                $sender->sendMessage(TextFormat::RED . "You are not in a party. Use /party create to make one!");
            }
        } else {
            $subCommand = strtolower(array_shift($args));
            switch ($subCommand) {
                case "create":
                    if ($party !== null) {
                        $sender->sendMessage(TextFormat::RED . "You are already in a party");
                        return true;
                    }
                    $partyManager->createParty($sender);
                    $sender->sendMessage(TextFormat::GREEN . "Party created");
                    break;
		case "help":
		    $create = "/party create - create a party\n";
		    $invite = "/party invite (name) - invite a player to the party\n";
		    $kick = "/party kick (name) - kick a player from the party\n";
		    $accept = "/party accept (name) - Accept a player party invite\n";
		    $leave = "/party leave - Leave the party\n";
		    $list = "/party list - Show the party list\n";
		    $show = "/party show - Show all parties\n";
		    $sender->sendMessage(TextFormat::RED . $create.$invite.$kick.$accept.$leave.$list.$show);
		    break;
                case "invite":
                    if ($party === null) {
                        $sender->sendMessage(TextFormat::RED . "You are not in a party. Use /party create to make one!");
                        return true;
                    }
                    if (!$party->isMember($sender)) {
                        $sender->sendMessage(TextFormat::RED . "You are not the party leader");
                        return true;
                    }
                    if (count($args) === 0) {
                        $sender->sendMessage(TextFormat::RED . "Usage: /party invite <player>");
                        return true;
                    }
                    $playerName = array_shift($args);
                    $player = $this->plugin->getServer()->getPlayerExact($playerName);
                    if ($player === null) {
                        $sender->sendMessage(TextFormat::RED . "Player not found: " . $playerName);
                        return true;
                    }
                    if ($party->isMember($player)) {
                        $sender->sendMessage(TextFormat::RED . $player->getName() . TextFormat::WHITE . " is already in your party");
                        return true;
                    }
                    $partyManager->invitePlayer($party, $sender, $player);
                    $sender->sendMessage(TextFormat::GREEN . "Invitation sent to " . TextFormat::WHITE . $player->getName());
                    break;
                case "kick":
                    if($party === null){
                        $sender->sendMessage(TextFormat::RED . "You are not in a party. Use /party create to make one!");
                        return true;
                    }
                    if (!$party->isMember($sender)) {
                        $sender->sendMessage(TextFormat::RED . "You are not the party leader");
                        return true;
                    }
                    if (count($args) === 0) {
                        $sender->sendMessage(TextFormat::RED . "Usage: /party kick <player>");
                        return true;
                    }
                    $playerName = array_shift($args);
                    $player = $this->plugin->getServer()->getPlayerExact($playerName);
                    if ($player === null) {
                        $sender->sendMessage(TextFormat::RED . "Player not found: " . $playerName);
                        return true;
                    }
                    if ($party->isMember($player)) {
                        $party->removeMember($player);
                        $sender->sendMessage(TextFormat::RED . $player->getName() . TextFormat::WHITE . " has been kicked");
                        return true;
                    }
                    $sender->sendMessage(TextFormat::RED . $player->getName() . TextFormat::WHITE . " Is not in your party!");
                    break;
                case "accept":
                    if ($party !== null) {
                        $sender->sendMessage(TextFormat::RED . "You are already in a party");
                        return true;
                    }
                    if (count($args) === 0) {
                        $sender->sendMessage(TextFormat::RED . "Usage: /party accept <player>");
                        return true;
                    }
                    $playerName = array_shift($args);
                    $player = $this->plugin->getServer()->getPlayerExact($playerName);
                    if ($player === null) {
                        $sender->sendMessage(TextFormat::RED . "Player not found: " . TextFormat::WHITE . $playerName);
                        return true;
                    }
                    $partyManager->acceptInvite($sender, $player);
                    break;
                case "leave":
                    if ($party === null) {
                        $sender->sendMessage(TextFormat::RED . "You are not in a party. Use /party create to make one!");
                        return true;
                    }
                    $partyManager->removePlayer($party, $sender);
                    $sender->sendMessage(TextFormat::GREEN . "You left the party");
                    break;
                case "list":
                    if ($party === null) {
                        $sender->sendMessage(TextFormat::RED . "You are not in a party");
                        return true;
                    }
                    if (!$party->isMember($sender)) {
                        $sender->sendMessage(TextFormat::RED . "You are not in the party");
                        return true;
                    }
                    $members = array_map(function ($player) {
                        return $player->getName();
                    }, $party->getMembers());
                    $sender->sendMessage(TextFormat:: GREEN . "Members: " . TextFormat::WHITE . implode(", ", $members));
                    break;
                case "show":
                    $parties = PartyAPI::getInstance()->getParties();
                    if (empty($parties)) {
                        $sender->sendMessage(TextFormat::RED . "There are no current parties right now!");
                        break;
                    }
                    $sender->sendMessage(TextFormat::GREEN . "Current parties: " . TextFormat::WHITE);
                    foreach ($parties as $party) {
                        $leader = $party->getLeader();
                        $members = $party->getMembers();
                        $message = $leader->getName() . TextFormat::RED . "'s party: ";
                        $message .= TextFormat::WHITE . "members: ";
                        foreach ($members as $member) {
                            $message .= $member->getName() . " ";
                        }
                        $sender->sendMessage($message);
                    }
                    break;
                default:
                    $sender->sendMessage(TextFormat::RED . "Unknown sub-command: use /party help for help");
            }
        }
        return true;
    }
}
