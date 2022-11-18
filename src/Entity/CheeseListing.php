<?php

namespace App\Entity;

use ApiPlatform\Doctrine\Orm\Filter\BooleanFilter;
use ApiPlatform\Doctrine\Orm\Filter\RangeFilter;
use ApiPlatform\Doctrine\Orm\Filter\SearchFilter;
use ApiPlatform\Metadata\ApiFilter;
use ApiPlatform\Metadata\ApiResource;
use ApiPlatform\Metadata\Delete;
use ApiPlatform\Metadata\Get;
use ApiPlatform\Metadata\GetCollection;
use ApiPlatform\Metadata\Post;
use ApiPlatform\Metadata\Put;
use ApiPlatform\Serializer\Filter\PropertyFilter;
use App\Doctrine\CheeseListingSetOwnerListener;
use App\Repository\CheeseListingRepository;
use App\Validator\IsValidOwner;
use Carbon\Carbon;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Serializer\Annotation\Groups;
use Symfony\Component\Serializer\Annotation\SerializedName;
use Symfony\Component\Validator\Constraints as Assert;

use function strlen;

#[ApiResource(
    shortName: "cheese",
    operations: [
        new Get(
            normalizationContext: [
                'groups' => [
                    'cheese:read',
                    'cheese:item:get',
                ],
            ]
        ),
        new GetCollection(),
        new Post(
            denormalizationContext: [
                'groups' => [
                    'cheese:collection:post',
                    'cheese:write',
                ],
            ],
            security: "is_granted('ROLE_USER')"
        ),
        new Put(
            security: "is_granted('EDIT', previous_object)",
            securityMessage: 'Only the creator can edit a cheese listing'
        ),
        new Delete(
            security: "is_granted('ROLE_ADMIN')"
        )
    ],
    formats: ['json', 'jsonld', 'html', 'jsonhal', 'csv' => ['text/csv']],
    normalizationContext: [
        'groups' => [
            'cheese:read',
            'swagger_definition_name' => 'Read'
        ]
    ],
    denormalizationContext: [
        'groups' => [
            'cheese:write',
            'swagger_definition_name' => 'Write'
        ]
    ],
    paginationItemsPerPage: 10
)]
#[ApiFilter(BooleanFilter::class, properties: ['isPublished'])]
#[ApiFilter(SearchFilter::class, properties: [
    'title' => 'partial',
    'description' => 'partial',
    'owner' => 'exact',
    'owner.username' => 'partial'
])]
#[ApiFilter(RangeFilter::class, properties: ['price'])]
#[ApiFilter(PropertyFilter::class)]

#[ORM\Entity(repositoryClass: CheeseListingRepository::class)]
#[ORM\EntityListeners([CheeseListingSetOwnerListener::class])]
class CheeseListing
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(type: 'string', length: 255)]
    #[Groups(['cheese:read', 'cheese:write', 'user:read', 'user:write'])]
    #[Assert\NotBlank]
    #[Assert\Length(
        [
            'min' => 2,
            'max' => 50,
            'maxMessage' => 'Describe your cheese in 50 chars or less',
        ]
    )]
    private string $title;

    #[ORM\Column(type: Types::TEXT)]
    #[Groups(["cheese:read"])]
    #[Assert\NotBlank]
    private string $description;

    /**
     * The price of this cheese, in cents
     */
    #[ORM\Column]
    #[Groups(['cheese:read', 'cheese:write', 'user:write', 'user:read'])]
    #[Assert\NotBlank]
    private int $price;

    #[ORM\Column]
    private ?\DateTimeImmutable $createdAt;

    #[ORM\Column(type: 'boolean')]
    #[Groups(['cheese:write'])]
    private ?bool $isPublished = false;

    #[ORM\ManyToOne(inversedBy: 'cheeseListings')]
    #[ORM\JoinColumn(nullable: false)]
    #[Groups(['cheese:read', 'cheese:collection:post'])]
    #[IsValidOwner]
    private ?User $owner = null;

    public function __construct(string $title = null)
    {
        $this->createdAt = new \DateTimeImmutable();
        $this->title = $title;
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getTitle(): ?string
    {
        return $this->title;
    }

    public function getDescription(): ?string
    {
        return $this->description;
    }

    #[Groups(['cheese:read'])]
    public function getShortDescription(): ?string
    {
        $description = strip_tags($this->description);
        if (strlen($description) < 40) {
            return $description;
        }

        return substr($description, 0, 40).'...';
    }

    public function setDescription(string $description): self
    {
        $this->description = $description;

        return $this;
    }

    #[Groups(['cheese:write', 'user:write'])]
    #[SerializedName('description')]
    public function setTextDescription(string $description): self
    {
        $this->description = nl2br($description);

        return $this;
    }

    public function getPrice(): ?int
    {
        return $this->price;
    }

    public function setPrice(int $price): self
    {
        $this->price = $price;

        return $this;
    }

    public function getCreatedAt(): ?\DateTimeImmutable
    {
        return $this->createdAt;
    }

    /**
     * How long ago in text that this cheese listing was added
     */
    #[Groups(['cheese:read'])]
    public function getCreatedAtAgo(): string
    {
        return Carbon::instance($this->createdAt)->diffForHumans();
    }


    public function isIsPublished(): ?bool
    {
        return $this->isPublished;
    }

    public function setIsPublished(bool $isPublished): self
    {
        $this->isPublished = $isPublished;

        return $this;
    }

    public function getOwner(): ?User
    {
        return $this->owner;
    }

    public function setOwner(?User $owner): self
    {
        $this->owner = $owner;

        return $this;
    }
}
