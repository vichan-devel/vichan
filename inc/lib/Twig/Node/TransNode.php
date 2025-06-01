<?php

declare(strict_types=1);

/*
 * This file is part of Twig I18n extension.
 *
 * (c) 2010-2019 Fabien Potencier
 * (c) 2019-2021 phpMyAdmin contributors
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Vichan\Twig\Extensions\Node;

use Twig\Attribute\YieldReady;
use Twig\Compiler;
use Twig\Node\CheckToStringNode;
use Twig\Node\Expression\AbstractExpression;
use Twig\Node\Expression\ConstantExpression;
use Twig\Node\Expression\FilterExpression;
use Twig\Node\Expression\Variable\ContextVariable;
use Twig\Node\Expression\Variable\LocalVariable;
use Twig\Node\Node;
use Twig\Node\PrintNode;
use Twig\Node\TextNode;

use function array_merge;
use function count;
use function sprintf;
use function str_replace;
use function trim;

/**
 * Represents a trans node.
 *
 * Author Fabien Potencier <fabien.potencier@symfony-project.com>
 */
#[YieldReady]
class TransNode extends Node
{
    /**
     * The label for gettext notes to be exported
     */
    public static string $notesLabel = '// notes: ';

    /**
     * Enable MoTranslator functions
     */
    public static bool $enableMoTranslator = false;

    /**
     * Enable calls to addDebugInfo
     */
    public static bool $enableAddDebugInfo = false;

    /**
     * Enables context functions usage
     */
    public static bool $hasContextFunctions = false;

    /** @phpstan-ignore constructor.unusedParameter */
    public function __construct(
        Node $body,
        Node|null $plural,
        AbstractExpression|null $count,
        Node|null $context = null,
        Node|null $notes = null,
        Node|null $domain = null,
        int $lineno = 0,
    ) {
        $nodes = ['body' => $body];
        if ($count instanceof \Twig\Node\Expression\AbstractExpression) {
            $nodes['count'] = $count;
        }

        if ($plural instanceof \Twig\Node\Node) {
            $nodes['plural'] = $plural;
        }

        if ($notes instanceof \Twig\Node\Node) {
            $nodes['notes'] = $notes;
        }

        if ($domain instanceof \Twig\Node\Node) {
            $nodes['domain'] = $domain;
        }

        if ($context instanceof \Twig\Node\Node) {
            $nodes['context'] = $context;
        }

        parent::__construct($nodes, [], $lineno);
    }

    /**
     * {@inheritdoc}
     */
    public function compile(Compiler $compiler)
    {
        if (self::$enableAddDebugInfo) {
            $compiler->addDebugInfo($this);
        }

        [$msg, $vars] = $this->compileString($this->getNode('body'));

        $hasPlural = $this->hasNode('plural');

        if ($hasPlural) {
            [$msg1, $vars1] = $this->compileString($this->getNode('plural'));

            $vars = array_merge($vars, $vars1);
        }

        $hasDomain = $this->hasNode('domain');
        $hasContext = $this->hasNode('context');

        $function = $this->getTransFunction($hasPlural, $hasContext, $hasDomain);

        if ($this->hasNode('notes')) {
            $message = trim((string) $this->getNode('notes')->getAttribute('data'));

            // line breaks are not allowed because we want a single line comment
            $message = str_replace(["\n", "\r"], ' ', $message);
            $compiler->raw(static::$notesLabel . $message . "\n");
        }

        if ($vars) {
            $compiler->raw('yield strtr(' . $function . '(');

            if ($hasDomain) {
                [$domain] = $this->compileString($this->getNode('domain'));
                $compiler
                    ->subcompile($domain)
                    ->raw(', ');
            }

            if ($hasContext && (static::$hasContextFunctions || static::$enableMoTranslator)) {
                [$context] = $this->compileString($this->getNode('context'));
                $compiler
                    ->subcompile($context)
                    ->raw(', ');
            }

            $compiler
                ->subcompile($msg);

            if ($hasPlural) {
                $compiler
                    ->raw(', ')
                    ->subcompile($msg1)
                    ->raw(', abs(')
                    ->subcompile($this->getNode('count'))
                    ->raw(')');
            }

            $compiler->raw('), array(');

            foreach ($vars as $var) {
                $attributeName = $var->getAttribute('name');
                if ($attributeName === 'count') {
                    $compiler
                        ->string('%count%')
                        ->raw(' => abs(')
                        ->subcompile($this->getNode('count'))
                        ->raw('), ');
                } else {
                    $compiler
                        ->string('%' . $attributeName . '%')
                        ->raw(' => ')
                        ->subcompile($var)
                        ->raw(', ');
                }
            }

            $compiler->raw("));\n");
        } else {
            $compiler->raw('yield ' . $function . '(');

            if ($hasDomain) {
                [$domain] = $this->compileString($this->getNode('domain'));
                $compiler
                    ->subcompile($domain)
                    ->raw(', ');
            }

            if ($hasContext && (static::$hasContextFunctions || static::$enableMoTranslator)) {
                [$context] = $this->compileString($this->getNode('context'));
                $compiler
                    ->subcompile($context)
                    ->raw(', ');
            }

            $compiler
                ->subcompile($msg);

            if ($hasPlural) {
                $compiler
                    ->raw(', ')
                    ->subcompile($msg1)
                    ->raw(', abs(')
                    ->subcompile($this->getNode('count'))
                    ->raw(')');
            }

            $compiler->raw(");\n");
        }
    }

    /**
     * Keep this method protected instead of private some implementations may use it
     *
     * @psalm-return array{Node, list<ContextVariable>}
     */
    protected function compileString(Node $body): array
    {
        if ($body instanceof ContextVariable || $body instanceof ConstantExpression || $body instanceof LocalVariable) {
            return [$body, []];
        }

        $vars = [];
        if (count($body) > 0) {
            $msg = '';

            foreach ($body as $node) {
                if ($node instanceof PrintNode) {
                    $n = $node->getNode('expr');
                    while ($n instanceof FilterExpression) {
                        $n = $n->getNode('node');
                    }

                    while ($n instanceof CheckToStringNode) {
                        $n = $n->getNode('expr');
                    }

                    $attributeName = $n->getAttribute('name');
                    $msg .= sprintf('%%%s%%', $attributeName);
                    $vars[] = new ContextVariable($attributeName, $n->getTemplateLine());
                } else {
                    /** @phpstan-var TextNode $node */
                    $msg .= $node->getAttribute('data');
                }
            }
        } else {
            $msg = $body->getAttribute('data');
        }

        return [new I18nNode(new ConstantExpression(trim((string) $msg), $body->getTemplateLine()), [], 0), $vars];
    }

    /**
     * Keep this protected to allow people to override it with their own logic
     */
    protected function getTransFunction(bool $hasPlural, bool $hasContext, bool $hasDomain): string
    {
        $functionPrefix = '';

        if (static::$enableMoTranslator) {
            // The functions are prefixed with an underscore
            $functionPrefix = '_';
        }

        // If it has not context function support or not MoTranslator
        if (! static::$hasContextFunctions && ! static::$enableMoTranslator) {
            // Not found on native PHP: dnpgettext, npgettext, dpgettext, pgettext
            // No domain plural context support
            // No domain context support
            // No context support
            // No plural context support

            if ($hasDomain) {
                // dngettext($domain, $msgid, $msgidPlural, $number);
                // dgettext($domain, $msgid);
                return $functionPrefix . ($hasPlural ? 'dngettext' : 'dgettext');
            }

            // ngettext($msgid, $msgidPlural, $number);
            // gettext($msgid);
            return $functionPrefix . ($hasPlural ? 'ngettext' : 'gettext');
        }

        if ($hasDomain) {
            if ($hasPlural) {
                // dnpgettext($domain, $msgctxt, $msgid, $msgidPlural, $number);
                // dngettext($domain, $msgid, $msgidPlural, $number);
                return $functionPrefix . ($hasContext ? 'dnpgettext' : 'dngettext');
            }

            // dpgettext($domain, $msgctxt, $msgid);
            // dgettext($domain, $msgid);
            return $functionPrefix . ($hasContext ? 'dpgettext' : 'dgettext');
        }

        if ($hasPlural) {
            // npgettext($msgctxt, $msgid, $msgidPlural, $number);
            // ngettext($msgid, $msgidPlural, $number);
            return $functionPrefix . ($hasContext ? 'npgettext' : 'ngettext');
        }

        // pgettext($msgctxt, $msgid);
        // gettext($msgid);
        return $functionPrefix . ($hasContext ? 'pgettext' : 'gettext');
    }
}