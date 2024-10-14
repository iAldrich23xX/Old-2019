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

use pocketmine\block\utils\BlockDataSerializer;
use pocketmine\block\utils\MinimumCostFlowCalculator;
use pocketmine\block\utils\SupportType;
use pocketmine\entity\Entity;
use pocketmine\event\block\BlockFormEvent;
use pocketmine\event\block\BlockSpreadEvent;
use pocketmine\item\Item;
use pocketmine\math\AxisAlignedBB;
use pocketmine\math\Facing;
use pocketmine\math\Vector3;
use pocketmine\world\sound\FizzSound;
use pocketmine\world\sound\Sound;
use function lcg_value;

abstract class Liquid extends Transparent{
	public const MAX_DECAY = 7;

	protected BlockIdentifierFlattened $idInfoFlattened;

	public int $adjacentSources = 0;

	protected ?Vector3 $flowVector = null;

	protected bool $falling = false;
	protected int $decay = 0; //PC "level" property
	protected bool $still = false;

	public function __construct(BlockIdentifierFlattened $idInfo, string $name, BlockBreakInfo $breakInfo){
		$this->idInfoFlattened = $idInfo;
		parent::__construct($idInfo, $name, $breakInfo);
	}

	public function getId() : int{
		return $this->still ? $this->idInfoFlattened->getSecondId() : parent::getId();
	}

	protected function writeStateToMeta() : int{
		return $this->decay | ($this->falling ? BlockLegacyMetadata::LIQUID_FLAG_FALLING : 0);
	}

	public function readStateFromData(int $id, int $stateMeta) : void{
		$this->decay = BlockDataSerializer::readBoundedInt("decay", $stateMeta & 0x07, 0, self::MAX_DECAY);
		$this->falling = ($stateMeta & BlockLegacyMetadata::LIQUID_FLAG_FALLING) !== 0;
		$this->still = $id === $this->idInfoFlattened->getSecondId();
	}

	public function getStateBitmask() : int{
		return 0b1111;
	}

	public function isFalling() : bool{ return $this->falling; }

	/** @return $this */
	public function setFalling(bool $falling) : self{
		$this->falling = $falling;
		return $this;
	}

	public function getDecay() : int{ return $this->decay; }

	/** @return $this */
	public function setDecay(int $decay) : self{
		if($decay < 0 || $decay > self::MAX_DECAY){
			throw new \InvalidArgumentException("Decay must be in range 0 ... " . self::MAX_DECAY);
		}
		$this->decay = $decay;
		return $this;
	}

	public function hasEntityCollision() : bool{
		return true;
	}

	public function canBeReplaced() : bool{
		return true;
	}

	public function canBeFlowedInto() : bool{
		return true;
	}

	public function isSolid() : bool{
		return false;
	}

	/**
	 * @return AxisAlignedBB[]
	 */
	protected function recalculateCollisionBoxes() : array{
		return [];
	}

	public function getSupportType(int $facing) : SupportType{
		return SupportType::NONE();
	}

	public function getDropsForCompatibleTool(Item $item) : array{
		return [];
	}

	public function getStillForm() : Block{
		$b = clone $this;
		$b->still = true;
		return $b;
	}

	public function getFlowingForm() : Block{
		$b = clone $this;
		$b->still = false;
		return $b;
	}

	abstract public function getBucketFillSound() : Sound;

	abstract public function getBucketEmptySound() : Sound;

	public function isSource() : bool{
		return !$this->falling && $this->decay === 0;
	}

	/**
	 * @return float
	 */
	public function getFluidHeightPercent() : float|int{
		return (($this->falling ? 0 : $this->decay) + 1) / 9;
	}

	public function isStill() : bool{
		return $this->still;
	}

	/**
	 * @return $this
	 */
	public function setStill(bool $still = true) : self{
		$this->still = $still;
		return $this;
	}

	protected function getEffectiveFlowDecay(Block $block) : int{
		if(!($block instanceof Liquid) || !$block->isSameType($this)){
			return -1;
		}

		return $block->falling ? 0 : $block->decay;
	}

	public function readStateFromWorld() : void{
		parent::readStateFromWorld();
		$this->flowVector = null;
	}

	/**
	 * Returns how many liquid levels are lost per block flowed horizontally. Affects how far the liquid can flow.
	 */
	public function getFlowDecayPerBlock() : int{
		return 1;
	}

	/**
	 * Returns the number of source blocks of this liquid that must be horizontally adjacent to this block in order for
	 * this block to become a source block itself, or null if the liquid does not exhibit source-forming behaviour.
	 */
	public function getMinAdjacentSourcesToFormSource() : ?int{
		return null;
	}

	protected function checkForHarden() : bool{
		return false;
	}

	protected function liquidCollide(Block $cause, Block $result) : bool{
		$ev = new BlockFormEvent($this, $result);
		$ev->call();
		if(!$ev->isCancelled()){
			$world = $this->position->getWorld();
			$world->setBlock($this->position, $ev->getNewState());
			//$world->addSound($this->position->add(0.5, 0.5, 0.5), new FizzSound(2.6 + (lcg_value() - lcg_value()) * 0.8));
		}
		return true;
	}
}
