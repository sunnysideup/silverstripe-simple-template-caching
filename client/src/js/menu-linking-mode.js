/**
 * if you build a full menu and you want linking mode, but you do not want to load everytime, then you use this script.
 * your menu must be like this:
 * ```
 * <nav>
 *   <ul>
 *     <li>
 *       <a href="link">Link</a>
 *       <ul>
 *         <li>
 *           <a href="link">Link</a>
 *         </li>
 *       </ul>
 *     </li>
 *   </ul>
 * </nav>
 * you may also consider only loading it once and after that just referring back to a localstorage piece of html.
 */

document.addEventListener('DOMContentLoaded', () => {
  const currentSiteTreeId = window.sitetreeid
  const navUl = document.querySelector('nav > ul')

  if (navUl && currentSiteTreeId) {
    const aElement = navUl.querySelector(`a[data-id="${currentSiteTreeId}"]`)

    if (aElement) {
      // Add 'current' class to the LI containing the current link
      const li = aElement.closest('li')
      li.classList.add('current')

      // Add 'section' class to all parent LI elements up to the starting UL
      let parentUl = li.closest('ul')
      while (parentUl && parentUl !== navUl) {
        const parentLi = parentUl.closest('li')
        if (parentLi) {
          parentLi.classList.add('section')
          parentUl = parentLi.closest('ul')
        } else {
          break
        }
      }
    }
  }
})
