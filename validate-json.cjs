const fs = require('fs');

try {
  const content = fs.readFileSync('public/pdf-generation-example.html', 'utf8');
  
  // Extract the JSON part
  const match = content.match(/const formData = ({[\s\S]*});/);
  if (match) {
    const jsonString = match[1];
    console.log('Found JSON, length:', jsonString.length);
    
    try {
      const parsed = JSON.parse(jsonString);
      console.log('JSON is valid!');
    } catch (e) {
      console.log('JSON parse error:', e.message);
      console.log('Error at position:', e.message.match(/position (\d+)/)?.[1]);
    }
  } else {
    console.log('No JSON found in the expected format');
  }
} catch (e) {
  console.log('File read error:', e.message);
}
