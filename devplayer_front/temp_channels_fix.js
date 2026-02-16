const fs = require('fs');
let content = fs.readFileSync('src/views/Channels.vue', 'utf8');

// Fix the syntax error - add missing if statement
content = content.replace(
  '    }\n      const catData = await categoriesRes.json();',
  '    }\n\n    // Carregar categorias\n    if (categoriesRes.ok) {\n      const catData = await categoriesRes.json();'
);

fs.writeFileSync('src/views/Channels.vue', content);
console.log('Fixed Channels.vue');
