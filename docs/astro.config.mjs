// @ts-check
import {defineConfig} from 'astro/config';
import starlight from '@astrojs/starlight';
import mermaid from 'astro-mermaid';

// https://astro.build/config
export default defineConfig({
    site: 'https://epsicube.dev',
    integrations: [
        mermaid({}),
        starlight({
            title: 'Epsicube Framework',
            description: 'Modular framework built on Laravel.',
            social: [{icon: 'github', label: 'GitHub', href: 'https://github.com/epsicube/framework'}],
            editLink: {
                baseUrl: 'https://github.com/epsicube/framework/edit/main/docs/',
            },
            logo: {
                dark: './src/assets/logo_lite_dark.svg',
                light: './src/assets/logo_lite_light.svg',
            },
            sidebar: [
                {
                    label: 'Getting Started',
                    autogenerate: {directory: 'getting-started'},
                },
                {
                    label: 'Usage',
                    autogenerate: {directory: 'usage'},
                },
                {
                    label: 'Writing module',
                    autogenerate: {directory: 'writing-module'},
                },
                {
                    label: 'Schemas',
                    autogenerate: {directory: 'schemas'},
                },
                {
                    label: 'Built-In',
                    autogenerate: {directory: 'built-in'},
                },
                {
                    label: 'Hypercore',
                    badge: {
                        text: 'Experimental',
                        variant: 'caution'
                    },
                    autogenerate: {directory: 'hypercore'},
                },
                {
                    label: 'Advanced',
                    autogenerate: {directory: 'advanced'},
                },
            ],
        }),
    ],
});
