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

use pocketmine\block\utils\AnyFacingTrait;
use pocketmine\block\utils\BlockDataSerializer;
use pocketmine\item\Item;
use pocketmine\math\Facing;
use pocketmine\math\Vector3;
use pocketmine\player\Player;
use pocketmine\world\BlockTransaction;
use pocketmine\world\sound\RedstonePowerOffSound;
use pocketmine\world\sound\RedstonePowerOnSound;

abstract class Button extends Flowable{
	use AnyFacingTrait;

	protected bool $pressed = false;

	protected function writeStateToMeta() : int{
		return BlockDataSerializer::writeFacing($this->facing) | ($this->pressed ? BlockLegacyMetadata::BUTTON_FLAG_POWERED : 0);
	}

	public function readStateFromData(int $id, int $stateMeta) : void{
		//TODO: in PC it's (6 - facing) for every meta except 0 (down)
		$this->facing = BlockDataSerializer::readFacing($stateMeta & 0x07);
		$this->pressed = ($stateMeta & BlockLegacyMetadata::BUTTON_FLAG_POWERED) !== 0;
	}

	public function getStateBitmask() : int{
		return 0b1111;
	}

	public function place(BlockTransaction $tx, Item $item, Block $blockReplace, Block $blockClicked, int $face, Vector3 $clickVector, ?Player $player = null) : bool{
		if($this->canBeSupportedBy($blockReplace->getSide(Facing::opposite($face)), $face)){
			$this->facing = $face;
			return parent::place($tx, $item, $blockReplace, $blockClicked, $face, $clickVector, $player);
		}
		return false;
	}

	private function canBeSupportedBy(Block $support, int $face) : bool{
		return $support->getSupportType($face)->hasCenterSupport();
	}
}
