import { defineConfig } from 'vitepress'

export default defineConfig({
  title: 'Opal',
  description: 'High-performance image processing for PHP, powered by libvips',

  base: '/opal/',

  head: [
    ['link', { rel: 'icon', href: '/favicon.ico' }],
    ['meta', { name: 'theme-color', content: '#ea580c' }],
    ['meta', { name: 'og:type', content: 'website' }],
    ['meta', { name: 'og:locale', content: 'en' }],
    ['meta', { name: 'og:site_name', content: 'Opal' }],
  ],

  themeConfig: {
    logo: '/logo.png',

    outline: [2, 3],

    nav: [
      { text: 'User Guide', link: '/guide/getting-started/what-is-opal' },
      { text: 'API Reference', link: '/api/' },
      {
        text: '1.0.0',
        items: [
          { text: 'Changelog', link: '/changelog' },
          { text: 'Contributing', link: '/contributing' },
        ]
      }
    ],

    sidebar: {
      '/guide/': [
        {
          text: 'Getting Started',
          collapsed: false,
          items: [
            { text: 'What is Opal?', link: '/guide/getting-started/what-is-opal' },
            { text: 'Installation', link: '/guide/getting-started/installation' },
            { text: 'Quick Start', link: '/guide/getting-started/quick-start' },
          ]
        },
        {
          text: 'Core Concepts',
          collapsed: false,
          items: [
            { text: 'Lazy Evaluation', link: '/guide/core-concepts/lazy-evaluation' },
            { text: 'NDArray Interop', link: '/guide/core-concepts/ndarray-interop' },
          ]
        },
      ],
      '/api/': [
        {
          text: 'API Reference',
          items: [
            { text: 'Overview', link: '/api/' },
            { text: 'Image Class', link: '/api/image' },
            { text: 'Options Classes', link: '/api/options' },
            { text: 'Support Classes', link: '/api/support' },
            { text: 'Enums', link: '/api/enums' },
            { text: 'Exceptions', link: '/api/exceptions' },
          ]
        },
      ]
    },

    socialLinks: [
      { icon: 'github', link: 'https://github.com/phpmlkit/opal' },
    ],

    footer: {
      message: 'Released under the MIT License.',
      copyright: 'Copyright © 2026 CodeWithKyrian'
    },

    search: {
      provider: 'local'
    },

    editLink: {
      pattern: 'https://github.com/phpmlkit/opal/edit/main/docs/:path',
      text: 'Edit this page on GitHub'
    },

    lastUpdated: {
      text: 'Updated at',
      formatOptions: {
        dateStyle: 'full',
        timeStyle: 'medium'
      }
    }
  }
})
