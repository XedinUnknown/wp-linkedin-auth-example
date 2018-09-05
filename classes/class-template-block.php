<?php
/**
 * Class `Template_Block`.
 *
 * @package RestApiImport
 */

declare( strict_types=1 );

namespace XedinUnknown\RestApiImport;

/**
 * A block that renders a template with context.
 *
 * @since 0.1
 * @package RestApiImport
 */
class Template_Block {

    /**
     * The template file path.
     *
     * @since 0.1
     *
     * @var string
     */
    protected $template;

    /**
     * The template values
     *
     * @since 0.1
     *
     * @var array
     */
    protected $context;

    public function __construct(string $template, array $context = []) {
        $this->template = $template;
        $this->context = $context;
    }

    /**
     * Retrieves the string representation of this instance.
     *
     * @since 0.1
     *
     * @return string The string representation.
     */
    public function __toString()
    {
        return $this->render();
    }

    /**
	 * Renders the instance.
	 *
	 * @since 0.1
	 * @return string The output.
	 */
	public function render(): string {
        extract($this->context);

        ob_start();
        include $this->template;
        $output = ob_get_clean();

        return $output;
	}
}
