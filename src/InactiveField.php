<?php

namespace Tbp\WP\Plugin\AcfFields;

class InactiveField
    extends
    Field
{

    protected function getFieldSettingsDefinition( array $addArray = [] ): array
    {

        return [];
    }


    protected function getFilterDefinition(): array
    {

        return [];
    }


    protected function ajax_query_helper()
    {
    }


    /**
     *  render_field()
     *
     *  Create the HTML interface for your field
     *
     * @since          3.6
     * @date           23/01/13
     *
     * @param    $field  (array) the $field being rendered
     *
     * @method-type    action
     *
     * @throws \ErrorException
     */
    public function render_field( $field )
    {

        // div attributes
        $attributes = [
            'id'    => $field['id'],
            'class' => "acf-tbp-collection acf-tbp-inactive acf-tbp-{$field['name']} {$field['class']}",
        ];

        printf( '<div %s', acf_esc_attrs( $attributes ) );
        acf_hidden_input(
            [
                'name'  => $field['name'],
                'value' => $field['value'] ?? '',
            ]
        );

        printf( '<p><strong>%s</strong></p>', $this->settings['inactive_reason'] );

        if ( ! empty( $field['value'] ) )
        {

            printf(
                <<<HTML
<p>%s</p>
                          <pre>
%s
                           </pre>
HTML
                ,
                __( 'Raw field value:', 'tbp-acf-fields' ),
                acf_esc_html( $field['value'] )
            );
        }

        echo '</div>';

    }

}
