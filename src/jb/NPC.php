<?php

namespace jb;

use pocketmine\Player;
use pocketmine\utils\UUID;
use pocketmine\entity\Creature;
use pocketmine\nbt\tag\ListTag;
use pocketmine\nbt\tag\IntTag;
use pocketmine\nbt\tag\FloatTag;
use pocketmine\nbt\tag\StringTag;
use pocketmine\nbt\tag\ByteTag;
use pocketmine\nbt\tag\DoubleTag;
use pocketmine\event\entity\EntityDamageEvent;
use pocketmine\event\entity\EntityDamageByEntityEvent;
use pocketmine\event\player\PlayerRespawnEvent;
use pocketmine\event\player\PlayerQuitEvent;;
use pocketmine\level\Position;
use pocketmine\item\Item;
use pocketmine\math\Vector3;
use pocketmine\network\protocol\AddEntityPacket;
use pocketmine\network\protocol\AddPlayerPacket;
use pocketmine\network\protocol\SetEntityMotionPacket;
use pocketmine\scheduler\CallbackTask;
use pocketmine\level\particle\DestroyBlockParticle;

class NPC extends Creature{
	
	public $networkId = 32;
	public $target;
	public $spawnPos;
	public $attackDamage = 1;
	public $attackRate = 10;
	public $attackDelay = 0;
	public $speed;
	public $drops;
	public $skin;
	public $heldItem;
	public $range;
        public $width = 0.6;
	public $length = 0.6;
	public $height = 1.8;
	public $eyeHeight = 1.62;
	public $knockbackTicks = 0;
        public $a;
	const NETWORK_ID = 1000;
	
	public function __construct($chunk,$nbt){
		parent::__construct($chunk,$nbt);
		$this->networkId = 63;
		$this->range = $this->namedtag["range"];
		$this->spawnPos = new Position($this->namedtag["spawnPos"][0],$this->namedtag["spawnPos"][1],$this->namedtag["spawnPos"][2],$this->level);
		$this->attackDamage = $this->namedtag["attackDamage"];
		$this->speed = $this->namedtag["speed"];
		$this->skin = $this->namedtag["skin"];
		$this->heldItem = new Item(0,0,0);
                $this->npc = "true";
                $this->type = $this->namedtag["type"];
                $this->a = 10;
	}
	
	public function initEntity(){
        parent::initEntity();
        $this->dataProperties[self::DATA_NO_AI] = [self::DATA_TYPE_BYTE, 1];
	$this->plugin = $this->server->getPluginManager()->getPlugin("mynpc");
		if(isset($this->namedtag->maxHealth)){
			parent::setMaxHealth($this->namedtag["maxHealth"]);
			$this->setHealth($this->namedtag["maxHealth"]);
		}else{
			$this->setMaxHealth(20);
			$this->setHealth(20);
		}
    }
	
	 public function getName(){
		return $this->getNameTag();
	}

   public function getMaxHealth(){
		return $this->namedtag["maxHealth"];
	}

   public function setMaxHealth($health){
		$this->namedtag->maxHealth = new IntTag("maxHealth",$health);
		parent::setMaxHealth($health);
  }
	
	public function spawnTo(Player $player){
		parent::spawnTo($player);
		if($this->networkId === 63){
			$pk = new AddPlayerPacket();
			$pk->uuid = UUID::fromData($this->getId(), $this->skin, $this->getNameTag());
			$pk->username = $this->getName();
			$pk->eid = $this->getId();
			$pk->x = $this->x;
			$pk->y = $this->y + $this->getEyeHeight() - 1.5;
			$pk->z = $this->z;
			$pk->speedX = $this->motionX;
			$pk->speedY = $this->motionY;
			$pk->speedZ = $this->motionZ;
			$pk->yaw = $this->yaw;
			$pk->pitch = $this->pitch;
			$pk->item = $this->heldItem;
			$pk->metadata = $this->dataProperties;
			$player->dataPacket($pk);
		}
	}
	
	public function saveNBT(){
        parent::saveNBT();
		$this->namedtag->maxHealth = new IntTag("maxHealth",$this->getMaxHealth());
		$this->namedtag->spawnPos = new ListTag("spawnPos", [
                new DoubleTag("", $this->spawnPos->x),
                new DoubleTag("", $this->spawnPos->y),
                new DoubleTag("", $this->spawnPos->z)
            ]);
		$this->namedtag->range = new FloatTag("range",$this->range);
		$this->namedtag->attackDamage = new FloatTag("attackDamage",$this->attackDamage);
		$this->namedtag->networkId = new IntTag("networkId",63);
		$this->namedtag->speed = new FloatTag("speed",$this->speed);
		$this->namedtag->skin = new StringTag("skin",$this->skin);
    $this->namedtag->npc = new StringTag("npc","true");
    $this->namedtag->heldItem= new StringTag("heldItem",$this->heldItem);
    $this->namedtag->type = new StringTag("type",$this->type);
    }
	
	public function onUpdate($currentTick = 1){
        switch($this->type){
        case 1:
        if($this->knockbackTicks > 0) $this->knockbackTicks--;
                $this->a--;
		if(($player = $this->target) && $player->isAlive()){
			if(isset($this->target) and ($this->target ===null)) unset($this->target);
			if($this->distanceSquared($this->spawnPos) > $this->range){
				$this->setPosition($this->spawnPos);
				$this->setHealth($this->getMaxHealth());
				$this->target = null;
			}else{
                                $z=$player->z-$this->z;
				$y=$player->y-$this->y;
				$x=$player->x-$this->x;
				$atn = atan2($z, $x);
				$ppos=$player->getPosition();
				  if($this->distance(new Vector3($ppos->getX(),$ppos->getY(),$ppos->getZ())) <= 0.8){
                                     if($this->a <= 0){
		                        $this->move($x/8,$y/1.2,$z/8);
		                	$ev = new EntityDamageByEntityEvent($this, $this->target, EntityDamageEvent::CAUSE_ENTITY_ATTACK, $this->attackDamage);
                                        $ev->setKnockBack(3);
		                        $this->target->sendPopup("你已受到{$this->attackDamage}的物理攻擊傷害");
					$player->attack($ev->getFinalDamage(), $ev);
                                        $this->a = 10;
                                            }                                          
					 }else{
				   $this->setRotation(rad2deg($atn -M_PI_2),rad2deg(-atan2($y, sqrt($x ** 2 + $z ** 2))));
                                   $this->move($x/8,$y/1.2,$z/8);
          if(mt_rand(0,25) <= 5){
           $this->setSneaking(true);
          }elseif(mt_rand(0,25) > 20){
          $this->setSneaking(false);
      }
     }
     }
   }
      break;
      case 2:
      $x = $this->x;$y = $this->y;$z = $this->z;
      $a = null;
      if($a = null){
      $a = "b";
      }
      if($this->getLevel()->getBlockIdAt($x + 1.2,$y,$z) !== 0){
      $a = "b";
      }elseif($this->getLevel()->getBlockIdAt($x-1.2,$y,$z) !== 0){
      $a = "c";
      }elseif($this->getLevel()->getBlockIdAt($x,$y,$z-1.2) !== 0){
      $a = "d";
      }elseif($this->getLevel()->getBlockIdAt($x,$y,$z+1.2) !== 0){
      $a = "e";
      }elseif($this->getLevel()->getBlockIdAt($x+1.2,$y,$z+1.2) !== 0){
      $a = "f";
      }elseif($this->getLevel()->getBlockIdAt($x-1.2,$y,$z-1.2) !== 0){
      $a = "g";
      }elseif($this->getLevel()->getBlockIdAt($x-1.2,$y,$z+1.2) !== 0){
      $a = "h";
      }elseif($this->getLevel()->getBlockIdAt($x+1.2,$y,$z-1.2) !== 0){
      $a = "i";
      }else{
      $a = "b";
}
       switch($a){
       case "b": $this->move(-1/8,0,0); break;
       case "c": $this->move(1/8,0,0); break;
       case  "d": $this->move(0,0,1/8); break;
       case "e": $this->move(0,0,-1/8); break;
       case "f": $this->move(-1/8,0,-1/8); break;
       case "g": $this->move(1/8,0,1/8); break;
       case "h": $this->move(-1/8,0,+1/8); break;
       case "i": $this->move(+1/8,0,-1/8);break;
       case "a": $this->move(1/8,0,0); break;
      }
      break;
      case 3:
      	//todo shooting arrows npc ai
      break;
   }
		$this->updateMovement();
		parent::onUpdate($currentTick);
		return !$this->closed;
	}
	
	public function attack($damage, EntityDamageEvent $source){
		if(!$source->isCancelled() && $source instanceof EntityDamageByEntityEvent){
			$dmg = $source->getDamager();
			if($dmg instanceof Player){
				$this->target = $dmg;
				parent::attack($damage, $source);
				$this->knockbackTicks = 10;
	 }
  }
  $this->plugin->blood($this);
}

  public function onRespawn(PlayerRespawnEvent $event){
		if(!$event->isCancelled() && isset($this->target)){
			if($this->target instanceof Player){
			 unset($this->target);
	 }
  }
}


 public function kill(){
 	        if($this->target !== null){
          $this->plugin->getServer()->broadcastMessage("恭喜玩家{$this->target->getName()}擊殺了{$this->getName()}");
           }
		parent::kill();
    $this->plugin->spaw($this->getNameTag(),$this->getLevel());
}

  public function onQuit(PlayerQuitEvent $event){
		if(!$event->isCancelled() && isset($this->target)){
			if($this->target instanceof Player){
			 unset($this->target);
	 }
  }
}

}
