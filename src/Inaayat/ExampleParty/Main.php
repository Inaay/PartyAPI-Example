<?php

namespace Inaayat\ExampleParty;

use Inaayat\ExampleParty\command\PartyCommand;
use pocketmine\plugin\PluginBase;

class Main extends PluginBase {

    public function onEnable(): void {
		$this->getServer()->getCommandMap()->register("party", new PartyCommand($this));
    }
}