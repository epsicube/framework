// @ts-check
import {defineConfig} from 'astro/config';
import starlight from '@astrojs/starlight';

// https://astro.build/config
export default defineConfig({
    site: 'https://unigale.quix-labs.com',
    integrations: [
        starlight({
            title: 'Unigale Framework',
            social: [{icon: 'github', label: 'GitHub', href: 'https://github.com/unigale/framework'}],
            sidebar: [
                {
                    label: 'Getting Started',
                    autogenerate: {directory: 'getting-started'},
                },
                {
                    label: 'Modules',
                    autogenerate: {directory: 'modules'},
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
