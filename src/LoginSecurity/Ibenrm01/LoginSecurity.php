<?php

namespace LoginSecurity\Ibenrm01;

use pocketmine\{
    Server, Player
};

use pocketmine\plugin\{
    Plugin, PluginBase
};

use pocketmine\command\{
    Command, CommandSender, ConsoleCommandSender, ExecutorCommand
};

use pocketmine\event\player\{
    PlayerLoginEvent, PlayerJoinEvent, PlayerQuitEvent, PlayerDeathEvent, PlayerInteractEvent, PlayerMoveEvent
};

use pocketmine\event\Listener;
use pocketmine\utils\{
    Config, TextFormat as C
};
use pocketmine\event\entity\{
    EntityDamageEvent, EntityDamageByEntityEvent
};
use pocketmine\event\block\{
    BlockPlaceEvent, BlockBreakEvent
};

class LoginSecurity extends PluginBase implements Listener {

    const MSG_LOGIN = "§l§eLOGIN §7// §r";
    const MSG_REGISTER = "§l§eREGISTER §7// §r";
    const MSG_CHANGE_PASSWORD = "§l§eCHANGE PASSWORD §7// §r";
    const MSG_REMOVE_PASSWORD = "§l§eREMOVE PASSWORD §7// §r";
    const MSG_MY_PASSWORD = "§l§ePASSWORD §7// §r";

    public $timer_change_password = [];
    public $timer_remove_password = [];

    public function onEnable(){
        $this->getServer()->getPluginManager()->registerEvents($this, $this);
        $this->getLogger()->info("§aPlugin Enabled");
        $this->saveDefaultConfig();
        @mkdir($this->getDataFolder()."/players/");
    }

    /**
     * @param EntityDamageByEntityEvent $event
     */
    public function onDamage(EntityDamageByEntityEvent $event){
        $player = $event->getEntity();
        if(file_exists($this->getDataFolder()."/players/".$player->getName().".yml")){
            $data = new Config($this->getDataFolder()."/players/".$player->getName().".yml", Config::YAML);
            if($data->exists("login")){
                if($data->get("login") == "null"){
                    $event->setCancelled();
                }
            } else {
                $event->setCancelled();
            }
        }
    }

    /**
     * @param BlockPlaceEvent $event
     */
    public function onPlace(BlockPlaceEvent $event){
        $player = $event->getPlayer();
        if(file_exists($this->getDataFolder()."/players/".$player->getName().".yml")){
            $data = new Config($this->getDataFolder()."/players/".$player->getName().".yml", Config::YAML);
            if($data->exists("login")){
                if($data->get("login") == "null"){
                    $event->setCancelled();
                }
            } else {
                $event->setCancelled();
            }
        }
    }

    /**
     * @param BlockBreakEvent $event
     */
    public function onBreak(BlockBreakEvent $event){
        $player = $event->getPlayer();
        if(file_exists($this->getDataFolder()."/players/".$player->getName().".yml")){
            $data = new Config($this->getDataFolder()."/players/".$player->getName().".yml", Config::YAML);
            if($data->exists("login")){
                if($data->get("login") == "null"){
                    $event->setCancelled();
                }
            } else {
                $event->setCancelled();
            }
        }
    }

    /**
     * @param PlayerJoinEvent $event
     */
    public function onJoin(PlayerJoinEvent $event){
        $player = $event->getPlayer();
        $data = new Config($this->getDataFolder()."/players/".$player->getName().".yml", Config::YAML);
        if($data->exists("password")){
            if($data->get("login") == "null"){
                $player->sendMessage(self::MSG_LOGIN.$this->getConfig()->get("please.login"));
            } elseif($data->get("login") == "success"){
                $player->sendMessage(self::MSG_LOGIN.$this->getConfig()->get("already.login"));
            }
        } else {
            $player->sendMessage(self::MSG_REGISTER.$this->getConfig()->get("unregistered.register"));
        }
    }

    /**
     * @param PlayerQuitEvent $event
     */
    public function onQuit(PlayerQuitEvent $event){
        $player = $event->getPlayer();
        $data = new Config($this->getDataFolder()."/players/".$player->getName().".yml", Config::YAML);
        if($data->exists("password")){
            $data->set("login", "null");
            $data->save();
        }
    }

    /**
     * @param PlayerMoveEvent $event
     */
    public function onMove(PlayerMoveEvent $event){
        $player = $event->getPlayer();
        $data = new Config($this->getDataFolder()."/players/".$player->getName().".yml", Config::YAML);
        if($data->exists("password")){
            if($data->get("login") == "null"){
                $player->setImmobile(true);
            } elseif($data->get("login") == "success"){
                $player->setImmobile(false);
            }
        } else {
            $player->setImmobile(true);
            $player->sendPopup($this->getConfig()->get("unregistered.register"));
        }
    }

    /**
     * @param Player $player
     */
    public function onMypass(Player $player){
        $data = new Config($this->getDataFolder()."/players/".$player->getName().".yml", Config::YAML);
        if($data->exists("password")){
            if($data->get("login") == "success"){
                $player->sendMessage(self::MSG_MY_PASSWORD."§aYour Password: §d".$data->get("password"));
            } else {
                $player->sendMessage(self::MSG_MY_PASSWORD."§bPlease /login (password)");
            }
        } else {
            $player->sendMessage(self::MSG_MY_PASSWORD."§bPlease /register (password) (repeat-password)");
        }
    }

    /**
     * @param Player $player
     */
    public function onRemovepass(Player $player){
        $data = new Config($this->getDataFolder()."/players/".$player->getName().".yml", Config::YAML);
        if($data->exists("password")){
            $data->remove("password");
            $data->remove("login");
            $data->save();
            $player->sendMessage(self::MSG_REMOVE_PASSWORD."§aYour Password Delete, §dPlease /register (password) (repeat-password)");
            $this->timer_remove_password[$player->getName()] = time() + 300; //per seconds
        } else {
            $player->sendMessage(self::MSG_REMOVE_PASSWORD.$this->getConfig()->get("unregistered.register"));
        }
    }

    /**
     * @param Player $player
     * @param string $oldpw
     * @param string $newpw
     */
    public function onChangepass(Player $player, string $oldpw, string $newpw){
        if(!isset($this->timer_change_password[$player->getName()])){
            $data = new Config($this->getDataFolder()."/players/".$player->getName().".yml", Config::YAML);
            if($data->exists("password")){
                if($data->get("password") == $oldpw){
                    $data->set("password", $newpw);
                    $data->save();
                    $player->sendMessage(self::MSG_CHANGE_PASSWORD.$this->getConfig()->get("success.changepass"));
                    $this->timer_change_password[$player->getName()] = time() + 1200; //per seconds
                } else {
                    $player->sendMessage(self::MSG_CHANGE_PASSWORD.$this->getConfig()->get("can't-same.changepass"));
                }
            } else {
                $player->sendMessage(self::MSG_CHANGE_PASSWORD.$this->getConfig()->get("unregistered.register"));
            }
        } else {
            if(time() < $this->timer_change_password[$player->getName()]){
                $cooldown = $this->timer_change_password[$player->getName()] - time();
                $player->sendMessage(self::MSG_CHANGE_PASSWORD."§cCooldown for change password again: §d".$cooldown." §aSeconds");
            } else {
                unset($this->timer_change_password[$player->getName()]);
            }
        }
    }

    /**
     * @param Player $player
     * @param string $firstpw
     * @param string $endpw
     */
    public function onRegister(Player $player, string $firstpw, string $endpw){
        $data = new Config($this->getDataFolder()."/players/".$player->getName().".yml", Config::YAML);
        if($data->exists("password")){
            $player->sendMessage(self::MSG_REGISTER.$this->getConfig()->get("already.register"));
        } else {
            $data->set("password", $endpw);
            $data->set("login", "success");
            $data->save();
            $player->sendMessage(self::MSG_REGISTER.$this->getConfig()->get("success.register"));
        }
    }

    public function onCommand(CommandSender $player, Command $cmd, string $label, array $args) :bool {
        switch($cmd->getName()){
            case "login":
            if($player instanceof Player){
                if(isset($args[0])){
                        $data = new Config($this->getDataFolder()."/players/".$player->getName().".yml", Config::YAML);
                        if($data->exists("password")){
                            if($data->get("password") != null){
                                if($args[0] == $data->getNested("password")){
                                    if($data->exists("login")){
                                        $data->set("login", "success");
                                        $data->save();
                                        $player->sendMessage(self::MSG_LOGIN.$this->getConfig()->get("success.login"));
                                        return true;
                                    } else {
                                        $player->sendMessage(self::MSG_LOGIN.$this->getConfig()->get("already.login"));
                                        return true;
                                    }
                                } else {
                                    $player->sendMessage(self::MSG_LOGIN.$this->getConfig()->get("password.wrong.login"));
                                    return true;
                                }
                            } else {
                                $player->sendMessage(self::MSG_LOGIN.$this->getConfig()->get("please.register.login"));
                                return true;
                            }
                        } else {
                            $player->sendMessage(self::MSG_LOGIN.$this->getConfig()->get("please.register.login"));
                            return true;
                        }
                } else {
                    $player->sendMessage(self::MSG_LOGIN."§b/login (password)");
                    return true;
                }
            } else {
                $player->sendMessage(self::MSG_LOGIN."§cPlease Use This Command in game");
                return true;
            }
            break;
            case "register":
            if($player instanceof Player){
                if(isset($args[0]) && isset($args[1])){
                    if(!isset($args[2])){
                        if($args[1] == $args[0]){
                            $this->onRegister($player, $args[0], $args[1]);
                            return true;
                        } else {
                            $player->sendMessage(self::MSG_REGISTER."§cPassword must be the same");
                            return true;
                        }
                    } else {
                        $player->sendMessage(self::MSG_REGISTER."§cPassword cannot contain spaces");
                        return true;
                    }
                } else {
                    $player->sendMessage(self::MSG_REGISTER."§b/register (password) (repeat-password)");
                    return true;
                }
            } else {
                $player->sendMessage(self::MSG_REGISTER."§cUse This Command in-game");
                return true;
            }
            break;
            case "changepass":
            if($player instanceof Player){
                if(isset($args[0]) && isset($args[1])){
                    if(!isset($args[2])){
                        if($args[1] != $args[0]){
                            $this->onChangepass($player, $args[0], $args[1]);
                            return true;
                        } else {
                            $player->sendMessage(self::MSG_CHANGE_PASSWORD."§cYou password don't can same, §drepeat again");
                            return true;
                        }
                    } else {
                        $player->sendMessage(self::MSG_CHANGE_PASSWORD."§cPassword cannot contain spaces");
                        return true;
                    }
                } else {
                    $player->sendMessage(self::MSG_CHANGE_PASSWORD."§b/changepass (old-password) (new-password)");
                    return true;
                }
            } else {
                $player->sendMessage(self::MSG_CHANGE_PASSWORD."§cPlease use this command in-game");
                return true;
            }
            break;
            case "rmpass":
            if($player->isOp()){
                if(isset($args[0])){
                    $target = $this->getServer()->getPlayer($args[0]);
                    if($target instanceof Player){
                        if(!isset($this->timer_remove_password[$player->getName()])){
                            $this->onRemovepass($target);
                            $player->sendMessage(self::MSG_REMOVE_PASSWORD."§aSuccess remove password §d".$target->getName());
                            return true;
                        } else {
                            if(time() < $this->timer_remove_password[$player->getName()]){
                                $cooldown = $this->timer_remove_password[$player->getName()];
                                $player->sendMessage(self::MSG_REMOVE_PASSWORD."§cCooldown for use this command again: §d".$cooldown." §aSeconds");
                                return true;
                            } else {
                                unset($this->timer_remove_password[$player->getName()]);
                                return true;
                            }
                        }
                    } else {
                        $player->sendMessage(self::MSG_REMOVE_PASSWORD."§cPlayer §d".$args[0]."§c not found");
                        return true;
                    }
                } else {
                    $player->sendMessage(self::MSG_REMOVE_PASSWORD."§b/rmpass (name-player)");
                    return true;
                }
            } else {
                $player->sendMessage(self::MSG_REMOVE_PASSWORD."§cYou don't have permission");
                return true;
            }
            break;
            case "mypass":
            if($player instanceof Player){
                $this->onMypass($player);
                return true;
            } else {
                $player->sendMessage(self::MSG_MY_PASSWORD."§cPlease Use This Command in-game");
                return true;
            }
            break;
        }
        return true;
    }
}
