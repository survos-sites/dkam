<?php

namespace App\Repository;

use App\Entity\AlbumRoots;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * Class description.
 * Second line
 */
final class AlbumRootsRepository extends ServiceEntityRepository
{
	public function __construct(
		public ManagerRegistry $registry,
	) {
		parent::__construct($registry, AlbumRoots::class);
	}
}
