<?php

/*
 *
 *  ____            _        _   __  __ _                  __  __ ____
 * |  _ \ ___   ___| | _____| |_|  \/  (_)_ __   ___      |  \/  |  _ \
 * | |_) / _ \ / __| |/ / _ \ __| |\/| | | '_ \ / _ \_____| |\/| | |_) |
 * |  __/ (_) | (__|   <  __/ |_| |  | | | | | |  __/_____| |  | |  __/
 * |_|   \___/ \___|_|\_\___|\__|_|  |_|_|_| |_|\___|     |_|  |_|_|
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU Lesser General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 *
 * @author PocketMine Team
 * @link http://www.pocketmine.net/
 *
 *
*/

declare(strict_types=1);

namespace pocketmine\block;

use pocketmine\event\block\LeavesDecayEvent;
use pocketmine\item\Item;
use pocketmine\item\Tool;
use pocketmine\level\Level;
use pocketmine\math\Vector3;
use pocketmine\Player;

class Leaves extends Transparent{
	const OAK = 0;
	const SPRUCE = 1;
	const BIRCH = 2;
	const JUNGLE = 3;
	const ACACIA = 0;
	const DARK_OAK = 1;

	protected $id = self::LEAVES;
	protected $woodType = self::LOG;

	public function __construct(int $meta = 0){
		$this->meta = $meta;
	}

	public function getHardness() : float{
		return 0.2;
	}

	public function getToolType() : int{
		return Tool::TYPE_SHEARS;
	}

	public function getRequiredHarvestLevel() : int{
		return Tool::REQUIRED;
	}

	public function getVariantBitmask() : int{
		return 0x03;
	}

	public function getName() : string{
		static $names = [
			self::OAK => "Oak Leaves",
			self::SPRUCE => "Spruce Leaves",
			self::BIRCH => "Birch Leaves",
			self::JUNGLE => "Jungle Leaves",
		];
		return $names[$this->meta & 0x03];
	}

	public function diffusesSkyLight() : bool{
		return true;
	}

	protected function findLog(Block $pos, array $visited, $distance, &$check, $fromSide = null){
		++$check;
		$index = $pos->x . "." . $pos->y . "." . $pos->z;
		if(isset($visited[$index])){
			return false;
		}
		if($pos->getId() === $this->woodType){
			return true;
		}elseif($pos->getId() === $this->id and $distance < 3){
			$visited[$index] = true;
			$down = $pos->getSide(Vector3::SIDE_DOWN)->getId();
			if($down === $this->woodType){
				return true;
			}
			if($fromSide === null){
				for($side = 2; $side <= 5; ++$side){
					if($this->findLog($pos->getSide($side), $visited, $distance + 1, $check, $side) === true){
						return true;
					}
				}
			}else{ //No more loops
				switch($fromSide){
					case Vector3::SIDE_NORTH:
						if($this->findLog($pos->getSide(Vector3::SIDE_NORTH), $visited, $distance + 1, $check, $fromSide) === true){
							return true;
						}elseif($this->findLog($pos->getSide(Vector3::SIDE_WEST), $visited, $distance + 1, $check, $fromSide) === true){
							return true;
						}elseif($this->findLog($pos->getSide(Vector3::SIDE_EAST), $visited, $distance + 1, $check, $fromSide) === true){
							return true;
						}
						break;
					case Vector3::SIDE_SOUTH:
						if($this->findLog($pos->getSide(Vector3::SIDE_SOUTH), $visited, $distance + 1, $check, $fromSide) === true){
							return true;
						}elseif($this->findLog($pos->getSide(Vector3::SIDE_WEST), $visited, $distance + 1, $check, $fromSide) === true){
							return true;
						}elseif($this->findLog($pos->getSide(Vector3::SIDE_EAST), $visited, $distance + 1, $check, $fromSide) === true){
							return true;
						}
						break;
					case Vector3::SIDE_WEST:
						if($this->findLog($pos->getSide(Vector3::SIDE_NORTH), $visited, $distance + 1, $check, $fromSide) === true){
							return true;
						}elseif($this->findLog($pos->getSide(Vector3::SIDE_SOUTH), $visited, $distance + 1, $check, $fromSide) === true){
							return true;
						}elseif($this->findLog($pos->getSide(Vector3::SIDE_WEST), $visited, $distance + 1, $check, $fromSide) === true){
							return true;
						}
						break;
					case Vector3::SIDE_EAST:
						if($this->findLog($pos->getSide(Vector3::SIDE_NORTH), $visited, $distance + 1, $check, $fromSide) === true){
							return true;
						}elseif($this->findLog($pos->getSide(Vector3::SIDE_SOUTH), $visited, $distance + 1, $check, $fromSide) === true){
							return true;
						}elseif($this->findLog($pos->getSide(Vector3::SIDE_EAST), $visited, $distance + 1, $check, $fromSide) === true){
							return true;
						}
						break;
				}
			}
		}

		return false;
	}

	public function onUpdate(int $type){
		if($type === Level::BLOCK_UPDATE_NORMAL){
			if(($this->meta & 0b00001100) === 0){
				$this->meta |= 0x08;
				$this->getLevel()->setBlock($this, $this, true, false);
			}
		}elseif($type === Level::BLOCK_UPDATE_RANDOM){
			if(($this->meta & 0b00001100) === 0x08){
				$this->meta &= 0x03;
				$visited = [];
				$check = 0;

				$this->getLevel()->getServer()->getPluginManager()->callEvent($ev = new LeavesDecayEvent($this));

				if($ev->isCancelled() or $this->findLog($this, $visited, 0, $check) === true){
					$this->getLevel()->setBlock($this, $this, false, false);
				}else{
					$this->getLevel()->useBreakOn($this);

					return Level::BLOCK_UPDATE_NORMAL;
				}
			}
		}

		return false;
	}

	public function place(Item $item, Block $block, Block $target, int $face, float $fx, float $fy, float $fz, Player $player = null) : bool{
		$this->meta |= 0x04;
		return $this->getLevel()->setBlock($this, $this, true);
	}

	public function getDrops(Item $item) : array{
		if($this->canBeBrokenWith($item)){
			return parent::getDrops($item);
		}else{
			$drops = [];

			if(mt_rand(1, 20) === 1){ //Saplings
				$drops[] = Item::get(Item::SAPLING, $this->meta & 0x03, 1);
			}
			if(($this->meta & 0x03) === self::OAK and mt_rand(1, 200) === 1){ //Apples
				$drops[] = Item::get(Item::APPLE, 0, 1);
			}

			return $drops;
		}
	}
}