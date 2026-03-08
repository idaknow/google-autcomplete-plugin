(async function ($) {
  try {
    const { PlaceAutocompleteElement } = await google.maps.importLibrary(
      "places"
    );

    function initAutocomplete(targetInputId, prefix) {
      const targetField = document.getElementById(targetInputId);
      if (!targetField) return;

      const fieldWrapper = targetField.closest(".form-row");
      if (!fieldWrapper) return;

      const existingAutocomplete = fieldWrapper.querySelector(
        ".gmp-autocomplete-wrapper"
      );
      if (existingAutocomplete) return;

      const autocompleteWrapper = document.createElement("div");
      autocompleteWrapper.className = "gmp-autocomplete-wrapper";
      autocompleteWrapper.style.marginBottom = "15px";

      // for some reason it won't work without a label
      const label = document.createElement("label");
      label.style.display = "none";

      const autocomplete = new PlaceAutocompleteElement({
        includedRegionCodes: ["nz"],
      });

      autocomplete.placeholder = "Start typing your address...";
      autocomplete.className = "gmp-autocomplete-input";

      autocompleteWrapper.appendChild(label);
      autocompleteWrapper.appendChild(autocomplete);

      const targetLabel = fieldWrapper.querySelector("label");
      fieldWrapper.insertBefore(autocompleteWrapper, targetLabel);

      autocomplete.addEventListener(
        "gmp-select",
        async ({ placePrediction }) => {
          const place = placePrediction.toPlace();

          await place.fetchFields({
            fields: ["addressComponents"],
          });

          if (!place || !place.addressComponents) return;

          let street = "";
          let city = "";
          let postcode = "";

          place.addressComponents.forEach((c) => {
            const type = c.types[0];

            if (type === "street_number") street = c.longText + " ";
            if (type === "route") street += c.longText;
            if (type === "locality" || type === "postal_town")
              city = c.longText;
            if (type === "postal_code") postcode = c.longText;
          });

          const setFieldValue = (id, value) => {
            let field = $("#" + id);
            if (field.length) {
              field.val(value).trigger("change");
              return true;
            } else {
              return false;
            }
          };

          setFieldValue(targetInputId, street.trim());
          setFieldValue(prefix + "city", city);
          setFieldValue(prefix + "postcode", postcode);

          $("body").trigger("update_checkout");

          // Optional: Clear the autocomplete input to prevent confusion
          autocomplete.value = "";
        }
      );
    }

    function initAll() {
      initAutocomplete("billing_address_1", "billing_");
      initAutocomplete("shipping_address_1", "shipping_");
    }

    $(document).ready(() => {
      initAll();
    });
    $(document.body).on("updated_checkout", () => {
      initAll();
    });
  } catch (e) {
    console.error("Failed to load Google Maps/Places library:", e);
  }
})(jQuery);
