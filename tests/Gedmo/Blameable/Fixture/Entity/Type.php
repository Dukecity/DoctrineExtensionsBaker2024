<?php

declare(strict_types=1);

/*
 * This file is part of the Doctrine Behavioral Extensions package.
 * (c) Gediminas Morkevicius <gediminas.morkevicius@gmail.com> http://www.gediminasm.org
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Gedmo\Tests\Blameable\Fixture\Entity;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

/**
 * @ORM\Entity
 */
#[ORM\Entity]
class Type
{
    /**
     * @var Collection<int, Article>
     *
     * @ORM\OneToMany(targetEntity="Article", mappedBy="type")
     */
    #[ORM\OneToMany(targetEntity: Article::class, mappedBy: 'type')]
    public Collection $articles;

    /**
     * @ORM\Id
     * @ORM\GeneratedValue
     * @ORM\Column(type="integer")
     */
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(type: Types::INTEGER)]
    private ?int $id = null;

    /**
     * @ORM\Column(name="title", type="string", length=128)
     */
    #[ORM\Column(name: 'title', type: Types::STRING, length: 128)]
    private string $title;

    public function __construct(string $title)
    {
        $this->articles = new ArrayCollection();

        $this->setTitle($title);
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getTitle(): string
    {
        return $this->title;
    }

    /**
     * @throws \InvalidArgumentException if the title is empty
     */
    public function setTitle(string $title): void
    {
        if ('' === trim($title)) {
            throw new \InvalidArgumentException('Title cannot be empty');
        }

        $this->title = $title;
    }
}
