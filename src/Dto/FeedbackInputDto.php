<?php

namespace App\Dto;

use Symfony\Component\Validator\Constraints as Assert;

final class FeedbackInputDto
{
    #[Assert\Email]
    #[Assert\NotBlank]
    public string $email = '';

    // @TODO: add choice constraint
    #[Assert\NotBlank]
    #[Assert\NoSuspiciousCharacters]
    public string $pattern = '';

    #[Assert\NotBlank]
    #[Assert\Length(min: 10, max: 5000)]
    #[Assert\NoSuspiciousCharacters]
    public string $message = '';

    public ?string $website = null;
}
