// @ts-check
import {defineConfig} from 'astro/config';
import starlight from '@astrojs/starlight';
import mermaid from 'astro-mermaid';

// https://astro.build/config
export default defineConfig({
    site: 'https://unigale.quix-labs.com',
    integrations: [
        mermaid({}),
        starlight({
            title: 'Unigale Framework',
            social: [{icon: 'github', label: 'GitHub', href: 'https://github.com/unigale/framework'}],
            editLink: {
                baseUrl: 'https://github.com/unigale/framework/edit/main/docs/',
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
