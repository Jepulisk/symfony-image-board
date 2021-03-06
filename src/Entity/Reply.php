<?php

namespace App\Entity;

use App\Repository\ReplyRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;

/**
 * @ORM\Entity(repositoryClass=ReplyRepository::class)
 */
class Reply
{
    /**
     * @ORM\Id()
     * @ORM\GeneratedValue()
     * @ORM\Column(type="integer")
     */
    private $id;

    /**
     * @ORM\Column(type="text", nullable=true)
     */
    private $content;

    /**
     * @ORM\Column(type="datetimetz")
     */
    private $ts_created;

    /**
     * @ORM\ManyToOne(targetEntity=Thread::class, inversedBy="replies")
     * @ORM\JoinColumn(nullable=false)
     */
    private $thread;

    /**
     * @ORM\ManyToMany(targetEntity=Reply::class, inversedBy="replies")
     */
    private $reply_to;

    /**
     * @ORM\ManyToMany(targetEntity=Reply::class, mappedBy="reply_to")
     */
    private $replies;

    /**
     * @ORM\Column(type="string", length=255, nullable=true)
     */
    private $attachment;

    /**
     * @ORM\ManyToOne(targetEntity=User::class, inversedBy="replies")
     */
    private $user;

    public function __construct()
    {
        $this->reply_to = new ArrayCollection();
        $this->replies = new ArrayCollection();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getContent(): ?string
    {
        return $this->content;
    }

    public function setContent(?string $content): self
    {
        $this->content = $content;

        return $this;
    }

    public function getTsCreated(): ?\DateTimeInterface
    {
        return $this->ts_created;
    }

    public function setTsCreated(\DateTimeInterface $ts_created): self
    {
        $this->ts_created = $ts_created;

        return $this;
    }

    public function getThread(): ?Thread
    {
        return $this->thread;
    }

    public function setThread(?Thread $thread): self
    {
        $this->thread = $thread;

        return $this;
    }

    /**
     * @return Collection|self[]
     */
    public function getReplyTo(): Collection
    {
        return $this->reply_to;
    }

    public function addReplyTo(self $replyTo): self
    {
        if (!$this->reply_to->contains($replyTo)) {
            $this->reply_to[] = $replyTo;
        }

        return $this;
    }

    public function removeReplyTo(self $replyTo): self
    {
        if ($this->reply_to->contains($replyTo)) {
            $this->reply_to->removeElement($replyTo);
        }

        return $this;
    }

    /**
     * @return Collection|self[]
     */
    public function getReplies(): Collection
    {
        return $this->replies;
    }

    public function addReply(self $reply): self
    {
        if (!$this->replies->contains($reply)) {
            $this->replies[] = $reply;
            $reply->addReplyTo($this);
        }

        return $this;
    }

    public function removeReply(self $reply): self
    {
        if ($this->replies->contains($reply)) {
            $this->replies->removeElement($reply);
            $reply->removeReplyTo($this);
        }

        return $this;
    }

    public function getAttachment(): ?string
    {
        return $this->attachment;
    }

    public function setAttachment(?string $attachment): self
    {
        $this->attachment = $attachment;

        return $this;
    }

    public function getUser(): ?User
    {
        return $this->user;
    }

    public function setUser(?User $user): self
    {
        $this->user = $user;

        return $this;
    }
}
