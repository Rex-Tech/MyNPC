<?php

namespace jb;

use pocketmine\Server;
use pocketmine\utils\TextFormat;
use pocketmine\plugin\PluginBase;
use pocketmine\command\Command;
use pocketmine\command\CommandSender;
use pocketmine\command\ConsoleCommandSender;
use pocketmine\block\Block;
use pocketmine\entity\Entity;
use pocketmine\item\Item;
use pocketmine\level\format\FullChunk;
use pocketmine\level\Level;
use pocketmine\level\Position;
use pocketmine\Player;
use pocketmine\math\Vector3;
use pocketmine\event\Listener;
use pocketmine\utils\Config;
use pocketmine\event\entity\EntityDeathEvent;
use pocketmine\nbt\tag\CompoundTag;
use pocketmine\nbt\tag\StringTag;
use pocketmine\nbt\tag\DoubleTag;
use pocketmine\nbt\tag\ListTag;
use pocketmine\nbt\tag\FloatTag;
use pocketmine\nbt\tag\ShortTag;
use pocketmine\nbt\tag\ByteTag;
use pocketmine\nbt\tag\IntTag;
use pocketmine\scheduler\PluginTask;
use pocketmine\level\particle\DestroyBlockParticle;

class kill extends PluginBase implements Listener{

public function onEnable(){
  
		$this->getServer()->getPluginManager()->registerEvents($this, $this);
		
		@mkdir($this->getDataFolder());
       
		$this->c=new Config($this->getDataFolder()."cfg.yml",Config::YAML,array());
		$this->skin=new Config($this->getDataFolder()."skin.yml",Config::YAML,array());

		$this->getLogger()->info(TextFormat::WHITE . "插件已启用！");
		$this->getLogger()->info(TextFormat::BLUE . "===========================");
		$this->getLogger()->info(TextFormat::YELLOW . "本插件由@CreeperGo编写，谢谢chenxiaoyi 的创意和支持 部分更新來自RexRed6802");
		$this->getLogger()->info(TextFormat::BLUE  . "---------------------------");
                Entity::registerEntity(NPC::class);
}

	public function dop($entity,$event){
		$it=[];
		foreach($this->cachec[$entity->getNameTag()]["drops"] as $k){
			$tm=explode(":",$k);
			if(mt_rand(0,100)<$tm[2]){
			$it=array_merge(array(new Item($tm[0],0,$tm[1])),$it);
			}
		}
		$event->setDrops($it);
	}

public function spaw($name,$level){
     $motion = new Vector3(0,0,0);
     $data = $this->c->get($name);
     $nbt = new CompoundTag("", [
            "Pos" => new ListTag("Pos", [
                new DoubleTag("", $data["x"]),
                new DoubleTag("", $data["y"]),
                new DoubleTag("", $data["z"])
            ]),
            "Motion" => new ListTag("Motion", [
                new DoubleTag("", 0),
                new DoubleTag("", 0),
                new DoubleTag("", 0)
            ]),
            "Rotation" => new ListTag("Rotation", [
                new FloatTag("", 0),
                new FloatTag("", 0)
            ]),
			"spawnPos" => new ListTag("spawnPos", [
                new DoubleTag("", $data["x"]),
                new DoubleTag("", $data["y"]),
                new DoubleTag("", $data["z"])
            ]),
			"range" => new FloatTag("range",$data["range"] * $data["range"]),
			"attackDamage" => new FloatTag("attackDamage",$data["damage"]),
			"networkId" => new IntTag("networkId",63),
			"speed" => new FloatTag("speed",$data["speed"]),
			"skin" => new StringTag("skin",$this->skin->get($name)),
            "heldItem"=> new StringTag("heldItem",$data["heldItem"]),
            "type" => new StringTag("type",$data["type"])
            ]);
	$entity=Entity::createEntity("NPC",$level->getChunk($data["x"] >> 4,$data["z"] >> 4,true),$nbt);
  	$entity->setMaxHealth($this->c->get($name)["health"]);
  	$entity->setHealth($this->c->get($name)["health"]);
        $entity->setNameTag($name);
	$entity->spawnToAll();
	return $entity;
}
 public function onCommand(CommandSender $sender, Command $cmd, $label, array $args){
        if($cmd == "new"){	
         if(!$sender->isOp()) return;		
          $held = $sender->getInventory()->getItemInHand();
	  $this->c->set($args[0],array(
	    "name"=>$args[0],
            "x"=>$sender->x,
            "y"=>$sender->y,
            "z"=>$sender->z,
            "type"=>$args[1],
            "level"=>$sender->level->getName(),
            "health"=>20,
            "range"=>10,
            "damage"=>1,
            "speed"=>1,
            "drops"=>"1;2;3",
            "heldItem"=>"276",
            "command"=>"/say player"           
            ));
	  $this->c->save();
	  $this->skin->set($args[0],bin2hex($sender->getSkinData()));
	  $this->skin->save(true);
	  $this->spaw($args[0],$sender->getLevel());
	  $sender->sendMessage("成功新增npc: $args[0]");
				}elseif($cmd == "clean"){
			            foreach($this->getServer()->getLevels() as $level){
				    foreach($level->getEntities() as $entity){
					if($entity instanceof Human and isset($entity->namedtag->npc)) $entity->close();
					$sender->sendMessage("test4");
		                }
	                }
                }
       }

  public function blood($entity){
	$entity->getLevel()->addParticle(new DestroyBlockParticle(new Vector3($entity->x, $entity->y, $entity->z), Block::get(152)));
}
}
