<?php declare(strict_types=1);

namespace Dormilich\RPSL\Attribute;

use Stringable;

use function explode;
use function rtrim;
use function str_contains;
use function strlen;
use function trim;

/**
 * Attribute value parsed from a string.
 */
class Value implements Stringable
{
    /**
     * @var string The name of the parent attribute.
     */
    private string $name;

    /**
     * @var string One value of the attribute.
     */
    private string $value;

    /**
     * @var string|null An inline comment about this attribute value.
     */
    private ?string $comment;

    /**
     * @var string|null The RPSL object type of referenced objects.
     */
    private ?string $type;

    /**
     * @param Attribute $attribute
     * @param string $value
     * @param string|null $comment
     * @param string|null $type
     */
    public function __construct(Attribute $attribute, string $value, ?string $comment = null, ?string $type = null)
    {
        $this->type = $type;
        $this->comment = $comment;

        $this->setFromString($value);
        $this->setName($attribute);
    }

    /**
     * @inheritDoc
     */
    public function __toString(): string
    {
        return trim($this->value . ' # ' . $this->comment, '# ');
    }

    /**
     * Returns the name of the parent attribute.
     *
     * @return string
     */
    public function getName(): string
    {
        return $this->name;
    }

    /**
     * @param Attribute $attribute
     */
    protected function setName(Attribute $attribute): void
    {
        $this->name = $attribute->getName();
    }

    /**
     * Returns the actual attribute value (without inline comment).
     *
     * @return string
     */
    public function getValue(): string
    {
        return $this->value;
    }

    /**
     * @param string $value
     */
    protected function setValue(string $value): void
    {
        $this->value = rtrim($value);
    }

    /**
     * Returns the associated inline comment.
     *
     * @return string|null
     */
    public function getComment(): ?string
    {
        return $this->comment;
    }

    /**
     * @param string $comment
     */
    protected function setComment(string $comment): void
    {
        $this->comment = trim($comment, "# \t\n\r\0\x0B");

        if ('' === $this->comment) {
            $this->comment = null;
        }
    }

    /**
     * Returns the RPSL object type (if known) for a referenced handle.
     *
     * @return string|null
     */
    public function getType(): ?string
    {
        return $this->type;
    }

    /**
     * Test if the value is not empty.
     *
     * @return bool
     */
    public function isDefined(): bool
    {
        return strlen($this->value) > 0;
    }

    /**
     * Test if the value is empty.
     *
     * @return bool
     */
    public function isEmpty(): bool
    {
        return !$this->isDefined();
    }

    /**
     * Extract inline comments from attribute lines.
     *
     * @param string $value
     * @return void
     */
    protected function setFromString(string $value): void
    {
        if (str_contains($value, '#')) {
            list($value, $comment) = explode('#', $value, 2);
            $this->setComment($comment);
        }

        $this->setValue($value);
    }
}
