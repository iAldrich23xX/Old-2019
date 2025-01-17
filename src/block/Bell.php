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

use pocketmine\block\tile\Bell as TileBell;
use pocketmine\block\utils\BellAttachmentType;
use pocketmine\block\utils\BlockDataSerializer;
use pocketmine\block\utils\HorizontalFacingTrait;
use pocketmine\block\utils\InvalidBlockStateException;
use pocketmine\block\utils\SupportType;
use pocketmine\item\Item;
use pocketmine\math\AxisAlignedBB;
use pocketmine\math\Facing;
use pocketmine\math\Vector3;
use pocketmine\player\Player;
use pocketmine\utils\AssumptionFailedError;
use pocketmine\world\BlockTransaction;
use pocketmine\world\sound\BellRingSound;

final class Bell extends Transparent{
	use HorizontalFacingTrait;

	private BellAttachmentType $attachmentType;

	public function __construct(BlockIdentifier $idInfo, string $name, BlockBreakInfo $breakInfo){
		$this->attachmentType = BellAttachmentType::FLOOR();
		parent::__construct($idInfo, $name, $breakInfo);
	}

	public function readStateFromData(int $id, int $stateMeta) : void{
		$this->setFacing(BlockDataSerializer::readLegacyHorizontalFacing($stateMeta & 0x03));

		$attachmentType = [
			BlockLegacyMetadata::BELL_ATTACHMENT_FLOOR => BellAttachmentType::FLOOR(),
			BlockLegacyMetadata::BELL_ATTACHMENT_CEILING => BellAttachmentType::CEILING(),
			BlockLegacyMetadata::BELL_ATTACHMENT_ONE_WALL => BellAttachmentType::ONE_WALL(),
			BlockLegacyMetadata::BELL_ATTACHMENT_TWO_WALLS => BellAttachmentType::TWO_WALLS()
		][($stateMeta >> 2) & 0b11] ?? null;
		if($attachmentType === null){
			throw new InvalidBlockStateException("No such attachment type");
		}
		$this->setAttachmentType($attachmentType);
	}

	public function writeStateToMeta() : int{
		$attachmentTypeMeta = [
			BellAttachmentType::FLOOR()->id() => BlockLegacyMetadata::BELL_ATTACHMENT_FLOOR,
			BellAttachmentType::CEILING()->id() => BlockLegacyMetadata::BELL_ATTACHMENT_CEILING,
			BellAttachmentType::ONE_WALL()->id() => BlockLegacyMetadata::BELL_ATTACHMENT_ONE_WALL,
			BellAttachmentType::TWO_WALLS()->id() => BlockLegacyMetadata::BELL_ATTACHMENT_TWO_WALLS
		][$this->getAttachmentType()->id()] ?? null;
		if($attachmentTypeMeta === null){
			throw new AssumptionFailedError("Mapping should cover all cases");
		}
		return BlockDataSerializer::writeLegacyHorizontalFacing($this->getFacing()) | ($attachmentTypeMeta << 2);
	}

	public function getStateBitmask() : int{
		return 0b1111;
	}

	protected function recalculateCollisionBoxes() : array{
		if($this->attachmentType->equals(BellAttachmentType::FLOOR())){
			return [
				AxisAlignedBB::one()->squash(Facing::axis($this->facing), 1 / 4)->trim(Facing::UP, 3 / 16)
			];
		}
		if($this->attachmentType->equals(BellAttachmentType::CEILING())){
			return [
				AxisAlignedBB::one()->contract(1 / 4, 0, 1 / 4)->trim(Facing::DOWN, 1 / 4)
			];
		}

		$box = AxisAlignedBB::one()
			->squash(Facing::axis(Facing::rotateY($this->facing, true)), 1 / 4)
			->trim(Facing::UP, 1 / 16)
			->trim(Facing::DOWN, 1 / 4);

		return [
			$this->attachmentType->equals(BellAttachmentType::ONE_WALL()) ? $box->trim($this->facing, 3 / 16) : $box
		];
	}

	public function getSupportType(int $facing) : SupportType{
		return SupportType::NONE();
	}

	public function getAttachmentType() : BellAttachmentType{ return $this->attachmentType; }

	/** @return $this */
	public function setAttachmentType(BellAttachmentType $attachmentType) : self{
		$this->attachmentType = $attachmentType;
		return $this;
	}

	private function canBeSupportedBy(Block $block, int $face) : bool{
		return !$block->getSupportType($face)->equals(SupportType::NONE());
	}

	public function place(BlockTransaction $tx, Item $item, Block $blockReplace, Block $blockClicked, int $face, Vector3 $clickVector, ?Player $player = null) : bool{
		if($face === Facing::UP){
			if(!$this->canBeSupportedBy($tx->fetchBlock($this->position->down()), Facing::UP)){
				return false;
			}
			if($player !== null){
				$this->setFacing(Facing::opposite($player->getHorizontalFacing()));
			}
			$this->setAttachmentType(BellAttachmentType::FLOOR());
		}elseif($face === Facing::DOWN){
			if(!$this->canBeSupportedBy($tx->fetchBlock($this->position->up()), Facing::DOWN)){
				return false;
			}
			$this->setAttachmentType(BellAttachmentType::CEILING());
		}else{
			$this->setFacing($face);
			if($this->canBeSupportedBy($tx->fetchBlock($this->position->getSide(Facing::opposite($face))), $face)){
				$this->setAttachmentType(BellAttachmentType::ONE_WALL());
			}else{
				return false;
			}
			if($this->canBeSupportedBy($tx->fetchBlock($this->position->getSide($face)), Facing::opposite($face))){
				$this->setAttachmentType(BellAttachmentType::TWO_WALLS());
			}
		}
		return parent::place($tx, $item, $blockReplace, $blockClicked, $face, $clickVector, $player);
	}

	public function onNearbyBlockChange() : void{
		if(
			($this->attachmentType->equals(BellAttachmentType::CEILING()) && !$this->canBeSupportedBy($this->getSide(Facing::UP), Facing::DOWN)) ||
			($this->attachmentType->equals(BellAttachmentType::FLOOR()) && !$this->canBeSupportedBy($this->getSide(Facing::DOWN), Facing::UP)) ||
			($this->attachmentType->equals(BellAttachmentType::ONE_WALL()) && !$this->canBeSupportedBy($this->getSide(Facing::opposite($this->facing)), $this->facing)) ||
			($this->attachmentType->equals(BellAttachmentType::TWO_WALLS()) && (!$this->canBeSupportedBy($this->getSide($this->facing), Facing::opposite($this->facing)) || !$this->canBeSupportedBy($this->getSide(Facing::opposite($this->facing)), $this->facing)))
		){
			$this->position->getWorld()->useBreakOn($this->position);
		}
	}

	public function onInteract(Item $item, int $face, Vector3 $clickVector, ?Player $player = null) : bool{
		if($player !== null){
			$faceHit = Facing::opposite($player->getHorizontalFacing());
			if(
				$this->attachmentType->equals(BellAttachmentType::CEILING()) ||
				($this->attachmentType->equals(BellAttachmentType::FLOOR()) && Facing::axis($faceHit) === Facing::axis($this->facing)) ||
				(
					($this->attachmentType->equals(BellAttachmentType::ONE_WALL()) || $this->attachmentType->equals(BellAttachmentType::TWO_WALLS())) &&
					($faceHit === Facing::rotateY($this->facing, false) || $faceHit === Facing::rotateY($this->facing, true))
				)
			){
				$this->ring($faceHit);
				return true;
			}
		}

		return false;
	}

	public function ring(int $faceHit) : void{
		$world = $this->position->getWorld();
		//$world->addSound($this->position, new BellRingSound());
		$tile = $world->getTile($this->position);
		if($tile instanceof TileBell){
			$world->broadcastPacketToViewers($this->position, $tile->createFakeUpdatePacket($faceHit));
		}
	}
}
