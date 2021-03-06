<?php /** @noinspection PhpIllegalPsrClassPathInspection */

/**
 * Copyright (c) 2020, Nosto Solutions Ltd
 * All rights reserved.
 *
 * Redistribution and use in source and binary forms, with or without
 * modification, are permitted provided that the following conditions are met:
 *
 * 1. Redistributions of source code must retain the above copyright notice,
 * this list of conditions and the following disclaimer.
 *
 * 2. Redistributions in binary form must reproduce the above copyright notice,
 * this list of conditions and the following disclaimer in the documentation
 * and/or other materials provided with the distribution.
 *
 * 3. Neither the name of the copyright holder nor the names of its contributors
 * may be used to endorse or promote products derived from this software without
 * specific prior written permission.
 *
 * THIS SOFTWARE IS PROVIDED BY THE COPYRIGHT HOLDERS AND CONTRIBUTORS "AS IS"
 * AND ANY EXPRESS OR IMPLIED WARRANTIES, INCLUDING, BUT NOT LIMITED TO, THE
 * IMPLIED WARRANTIES OF MERCHANTABILITY AND FITNESS FOR A PARTICULAR PURPOSE
 * ARE DISCLAIMED. IN NO EVENT SHALL THE COPYRIGHT HOLDER OR CONTRIBUTORS BE
 * LIABLE FOR ANY DIRECT, INDIRECT, INCIDENTAL, SPECIAL, EXEMPLARY, OR
 * CONSEQUENTIAL DAMAGES (INCLUDING, BUT NOT LIMITED TO, PROCUREMENT OF
 * SUBSTITUTE GOODS OR SERVICES; LOSS OF USE, DATA, OR PROFITS; OR BUSINESS
 * INTERRUPTION) HOWEVER CAUSED AND ON ANY THEORY OF LIABILITY, WHETHER IN
 * CONTRACT, STRICT LIABILITY, OR TORT (INCLUDING NEGLIGENCE OR OTHERWISE)
 * ARISING IN ANY WAY OUT OF THE USE OF THIS SOFTWARE, EVEN IF ADVISED OF THE
 * POSSIBILITY OF SUCH DAMAGE.
 *
 * @author Nosto Solutions Ltd <shopware@nosto.com>
 * @copyright Copyright (c) 2020 Nosto Solutions Ltd (http://www.nosto.com)
 * @license http://opensource.org/licenses/BSD-3-Clause BSD 3-Clause
 */

use Doctrine\ORM\AbstractQuery;
use Doctrine\ORM\Query\Expr\Join;
use Shopware\Models\Category\Category;

/**
 * Class Shopware_Plugins_Frontend_NostoTagging_Models_Product_Repository
 */
class Shopware_Plugins_Frontend_NostoTagging_Models_Product_Repository
{
    /**
     * Returns an array of articles id's that are active
     * and has the same category id of the given shopware category
     *
     * @noinspection MoreThanThreeArgumentsInspection
     * @param Category $category
     * @param $pageSize
     * @param $currentOffset
     * @param $id
     * @return array
     */
    public function getActiveArticlesIdsByCategory(
        Category $category,
        $pageSize,
        $currentOffset,
        $id
    ) {
        $builder = Shopware()->Models()->createQueryBuilder();
        $result = $builder->select('articles.id')
            ->from('\Shopware\Models\Article\Article', 'articles')
            ->innerJoin(
                '\Shopware\Models\Article\Detail',
                'details',
                Join::WITH,
                'articles.mainDetailId = details.id'
            )
            ->innerJoin(
                'articles.categories',
                'c'
            )
            ->where('articles.active = 1')
            ->andWhere('c.path LIKE :path')
            // Since the path in the database is saved with || between
            // the parents ids, we concatenate those and get all child
            // categories from the given language.
            ->setParameter('path', '%|' . (int)$category->getId() . '|%');
        if (!empty($id)) {
            $result = $result->andWhere('details.number = :id')
                ->setParameter('id', $id)
                ->getQuery();
        } else {
            $result = $result->orderBy('articles.added', 'DESC')
                ->setFirstResult($currentOffset)
                ->setMaxResults($pageSize)
                ->getQuery();
        }
        return $result->getResult(AbstractQuery::HYDRATE_ARRAY);
    }
}
