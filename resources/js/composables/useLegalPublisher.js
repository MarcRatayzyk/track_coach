import { usePage } from '@inertiajs/vue3';
import { computed } from 'vue';

export function useLegalPublisher() {
    const page = usePage();

    const publisher = computed(() => page.props.legal?.publisher ?? {});
    const hosting = computed(() => page.props.legal?.hosting ?? {});

    const vars = computed(() => ({
        publisherName: publisher.value.name ?? '',
        publisherLegalForm: publisher.value.legal_form ?? '',
        publisherAddress: publisher.value.address ?? '',
        publisherSiret: publisher.value.siret ?? '',
        publisherRcs: publisher.value.rcs ?? '',
        publisherVat: publisher.value.vat ?? '',
        publisherCapital: publisher.value.capital ?? '',
        publisherDirector: publisher.value.director ?? '',
        publisherEmail: publisher.value.email ?? '',
        hostingName: hosting.value.name ?? '',
        hostingAddress: hosting.value.address ?? '',
        hostingPhone: hosting.value.phone ?? '',
        hostingWebsite: hosting.value.website ?? '',
    }));

    return { publisher, hosting, vars };
}
