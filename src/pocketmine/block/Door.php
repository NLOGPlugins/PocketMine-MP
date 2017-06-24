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

use pocketmine\item\Item;
use pocketmine\level\Level;
use pocketmine\level\sound\DoorSound;
use pocketmine\math\AxisAlignedBB;
use pocketmine\math\Vector3;
use pocketmine\Player;


abstract class Door extends Transparent{

	protected $variantBitmask = 0;

	public function isSolid() : bool{
		return false;
	}

	private function getFullDamage(){
		$damage = $this->getDamage();
		$isUp = ($damage & 0x08) > 0;

		if($isUp){
			$down = $this->getSide(Vector3::SIDE_DOWN)->getDamage();
			$up = $damage;
		}else{
			$down = $damage;
			$up = $this->getSide(Vector3::SIDE_UP)->getDamage();
		}

		$isRight = ($up & 0x01) > 0;

		return $down & 0x07 | ($isUp ? 8 : 0) | ($isRight ? 0x10 : 0);
	}

	protected function recalculateBoundingBox(){

		$f = 0.1875;
		$damage = $this->getFullDamage();

		$bb = new AxisAlignedBB(
			$this->x,
			$this->y,
			$this->z,
			$this->x + 1,
			$this->y + 2,
			$this->z + 1
		);

		$j = $damage & 0x03;
		$isOpen = (($damage & 0x04) > 0);
		$isRight = (($damage & 0x10) > 0);

		if($j === 0){
			if($isOpen){
				if(!$isRight){
					$bb->setBounds(
						$this->x,
						$this->y,
						$this->z,
						$this->x + 1,
						$this->y + 1,
						$this->z + $f
					);
				}else{
					$bb->setBounds(
						$this->x,
						$this->y,
						$this->z + 1 - $f,
						$this->x + 1,
						$this->y + 1,
						$this->z + 1
					);
				}
			}else{
				$bb->setBounds(
					$this->x,
					$this->y,
					$this->z,
					$this->x + $f,
					$this->y + 1,
					$this->z + 1
				);
			}
		}elseif($j === 1){
			if($isOpen){
				if(!$isRight){
					$bb->setBounds(
						$this->x + 1 - $f,
						$this->y,
						$this->z,
						$this->x + 1,
						$this->y + 1,
						$this->z + 1
					);
				}else{
					$bb->setBounds(
						$this->x,
						$this->y,
						$this->z,
						$this->x + $f,
						$this->y + 1,
						$this->z + 1
					);
				}
			}else{
				$bb->setBounds(
					$this->x,
					$this->y,
					$this->z,
					$this->x + 1,
					$this->y + 1,
					$this->z + $f
				);
			}
		}elseif($j === 2){
			if($isOpen){
				if(!$isRight){
					$bb->setBounds(
						$this->x,
						$this->y,
						$this->z + 1 - $f,
						$this->x + 1,
						$this->y + 1,
						$this->z + 1
					);
				}else{
					$bb->setBounds(
						$this->x,
						$this->y,
						$this->z,
						$this->x + 1,
						$this->y + 1,
						$this->z + $f
					);
				}
			}else{
				$bb->setBounds(
					$this->x + 1 - $f,
					$this->y,
					$this->z,
					$this->x + 1,
					$this->y + 1,
					$this->z + 1
				);
			}
		}elseif($j === 3){
			if($isOpen){
				if(!$isRight){
					$bb->setBounds(
						$this->x,
						$this->y,
						$this->z,
						$this->x + $f,
						$this->y + 1,
						$this->z + 1
					);
				}else{
					$bb->setBounds(
						$this->x + 1 - $f,
						$this->y,
						$this->z,
						$this->x + 1,
						$this->y + 1,
						$this->z + 1
					);
				}
			}else{
				$bb->setBounds(
					$this->x,
					$this->y,
					$this->z + 1 - $f,
					$this->x + 1,
					$this->y + 1,
					$this->z + 1
				);
			}
		}

		return $bb;
	}

	public function onUpdate(int $type){
		if($type === Level::BLOCK_UPDATE_NORMAL){
			if($this->getSide(Vector3::SIDE_DOWN)->getId() === self::AIR){ //Replace with common break method
				$this->getLevel()->setBlock($this, Block::get(Block::AIR), false);
				if($this->getSide(Vector3::SIDE_UP) instanceof Door){
					$this->getLevel()->setBlock($this->getSide(Vector3::SIDE_UP), Block::get(Block::AIR), false);
				}

				return Level::BLOCK_UPDATE_NORMAL;
			}
		}

		return false;
	}

	public function place(Item $item, Block $block, Block $target, int $face, float $fx, float $fy, float $fz, Player $player = null) : bool{
		if($face === 1){
			$blockUp = $this->getSide(Vector3::SIDE_UP);
			$blockDown = $this->getSide(Vector3::SIDE_DOWN);
			if($blockUp->canBeReplaced() === false or $blockDown->isTransparent() === true){
				return false;
			}
			$direction = $player instanceof Player ? $player->getDirection() : 0;
			$face = [
				0 => 3,
				1 => 4,
				2 => 2,
				3 => 5,
			];
			$next = $this->getSide($face[(($direction + 2) % 4)]);
			$next2 = $this->getSide($face[$direction]);
			$metaUp = 0x08;
			if($next->getId() === $this->getId() or ($next2->isTransparent() === false and $next->isTransparent() === true)){ //Door hinge
				$metaUp |= 0x01;
			}

			$this->setDamage($player->getDirection() & 0x03);
			$this->getLevel()->setBlock($block, $this, true, true); //Bottom
			$this->getLevel()->setBlock($blockUp, $b = Block::get($this->getId(), $metaUp), true); //Top
			return true;
		}

		return false;
	}

	public function onBreak(Item $item) : bool{
		if(($this->getDamage() & 0x08) === 0x08){
			$down = $this->getSide(Vector3::SIDE_DOWN);
			if($down->getId() === $this->getId()){
				$this->getLevel()->setBlock($down, Block::get(Block::AIR), true);
			}
		}else{
			$up = $this->getSide(Vector3::SIDE_UP);
			if($up->getId() === $this->getId()){
				$this->getLevel()->setBlock($up, Block::get(Block::AIR), true);
			}
		}
		$this->getLevel()->setBlock($this, Block::get(Block::AIR), true);

		return true;
	}

	public function onActivate(Item $item, Player $player = null) : bool{
		if(($this->getDamage() & 0x08) === 0x08){ //Top
			$down = $this->getSide(Vector3::SIDE_DOWN);
			if($down->getId() === $this->getId()){
				$meta = $down->getDamage() ^ 0x04;
				$this->level->setBlock($down, Block::get($this->getId(), $meta), true);
				$this->level->addSound(new DoorSound($this));
				return true;
			}

			return false;
		}else{
			$this->meta ^= 0x04;
			$this->level->setBlock($this, $this, true);
			$this->level->addSound(new DoorSound($this));
		}

		return true;
	}

	public function getAffectedBlocks() : array{
		return [$this, $this->getSide(($this->meta & 0x08) === 0x08 ? Vector3::SIDE_DOWN : Vector3::SIDE_UP)];
	}
}