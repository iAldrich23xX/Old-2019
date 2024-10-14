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
use pocketmine\block\utils\Fallable;
use pocketmine\block\utils\FallableTrait;
use pocketmine\block\utils\SupportType;
use pocketmine\event\block\BlockMeltEvent;
use pocketmine\item\Item;
use pocketmine\item\VanillaItems;
use pocketmine\math\AxisAlignedBB;
use pocketmine\math\Facing;
use pocketmine\math\Vector3;
use pocketmine\player\Player;
use pocketmine\world\BlockTransaction;
use function floor;
use function max;

class SnowLayer extends Flowable {
	use FallableTrait;

	public const MIN_LAYERS = 1;
	public const MAX_LAYERS = 8;

	protected int $layers = self::MIN_LAYERS;

	protected function writeStateToMeta() : int{
		return $this->layers - 1;
	}

	public function readStateFromData(int $id, int $stateMeta) : void{
		$this->layers = BlockDataSerializer::readBoundedInt("layers", $stateMeta + 1, self::MIN_LAYERS, self::MAX_LAYERS);
	}

	public function getStateBitmask() : int{
		return 0b111;
	}

	public function getSupportType(int $facing) : SupportType{
		if(!$this->canBeReplaced()){
			return SupportType::FULL();
		}
		return SupportType::NONE();
	}

	private function canBeSupportedBy(Block $b) : bool{
		return $b->getSupportType(Facing::UP)->equals(SupportType::FULL());
	}

	public function place(BlockTransaction $tx, Item $item, Block $blockReplace, Block $blockClicked, int $face, Vector3 $clickVector, ?Player $player = null) : bool{
		if($blockReplace instanceof SnowLayer){
			if($blockReplace->layers >= self::MAX_LAYERS){
				return false;
			}
			$this->layers = $blockReplace->layers + 1;
		}
		if($this->canBeSupportedBy($blockReplace->getSide(Facing::DOWN))){
			return parent::place($tx, $item, $blockReplace, $blockClicked, $face, $clickVector, $player);
		}

		return false;
	}

	public function getDropsForCompatibleTool(Item $item) : array{
		return [
			VanillaItems::SNOWBALL()->setCount(max(1, (int) floor($this->layers / 2)))
		];
	}
}
