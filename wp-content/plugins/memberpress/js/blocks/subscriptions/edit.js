import { __ } from '@wordpress/i18n';
import { InspectorControls } from "@wordpress/block-editor";
import { PanelBody, Disabled, TextareaControl, ToggleControl } from "@wordpress/components";

import { useBlockProps } from '@wordpress/block-editor';
import ServerSideRender from '@wordpress/server-side-render';

export default function Edit({ attributes, setAttributes }) {
  const blockProps = useBlockProps();
  const {
    not_logged_in_message,
    no_subscriptions_message,
    top_description,
    bottom_description,
    use_access_url
  } = attributes;

  return (
    <div {...blockProps}>
      <InspectorControls>
        <PanelBody title="MemberPress Login Options" initialOpen={true}>
          <TextareaControl
            label={ __("Not Logged In Message:", "memberpress") }
            value={ not_logged_in_message }
            onChange={(val) => setAttributes({ not_logged_in_message: val })}
          />

          <TextareaControl
            label={ __("No Subscriptions Message:", "memberpress") }
            value={ no_subscriptions_message }
            onChange={(val) => setAttributes({ no_subscriptions_message: val })}
          />

          <TextareaControl
            label={ __("Top Description (optional):", "memberpress") }
            value={ top_description }
            onChange={(val) => setAttributes({ top_description: val })}
          />

          <TextareaControl
            label={ __("Bottom Description (optional):", "memberpress") }
            value={ bottom_description }
            onChange={(val) => setAttributes({ bottom_description: val })}
          />

          <ToggleControl
            label={ __("Use Membership Access URLs", "memberpress") }
            help={ __("Makes the Subscription name clickable, pointing to the Membership Access URL you have set in the Membership settings (Advanced tab)", "memberpress") }
            checked={ use_access_url }
            onChange={() => setAttributes({ use_access_url: !use_access_url })}
          />

        </PanelBody>
      </InspectorControls>

      <Disabled>
        <ServerSideRender
          block="memberpress/subscriptions"
          attributes={{
            not_logged_in_message,
            no_subscriptions_message,
            top_description,
            bottom_description,
            use_access_url
          }}
        />
      </Disabled>
    </div>
  );
}
